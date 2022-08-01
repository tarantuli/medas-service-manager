<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\ServiceManager\Cache\CacheManager;
use Medas\ServiceManager\Interfaces\{Cache, Package, PrimesCache};
use Medas\ServiceManager\ParameterResolver\ParameterResolver;

class ServiceManager implements PrimesCache
{
    const SERVICE_MAPPING_CACHE_KEY = ServiceMapping::class;

    private static self $instance;

    public static function postInstall(): void
    {
        service(CacheManager::class)->clearAll();

        foreach (self::get()->registeredPackages as $package) {
            $package->postInstall();
        }
    }

    public static function get(Cache|null $cache = null): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($cache);
        }

        return self::$instance;
    }

    /** @var object[] */
    private array $services = [];

    private ServiceMapping $mapping;
    private MappingCompiler $mappingCompiler;

    private bool $mappingWasCached = true;

    private CacheManager $cacheManager;
    private ServiceInstantiator $instantiator;

    /** @var Package[] */
    private array $registeredPackages = [];

    private function __construct(Cache|null $cache = null)
    {
        $this->services[self::class] = $this;

        $this->cacheManager = new CacheManager();

        if ($cache) {
            $this->cacheManager->register($cache);
        }

        $this->mappingCompiler = new MappingCompiler($this->cacheManager);
        $this->instantiator = new ServiceInstantiator($this);

        $this->mapping = $this->cacheManager->get()->get(
            self::SERVICE_MAPPING_CACHE_KEY,
            fn() => $this->initializeMapping()
        );

        $this->addPackage(ServiceManagerPackage::instance());
    }

    private function initializeMapping(): ServiceMapping
    {
        $this->mappingWasCached = false;

        return $this->mappingCompiler->get();
    }

    public function addPackage(Package $package): void
    {
        if (array_key_exists($package::class, $this->registeredPackages)) {
            return;
        }

        $this->addPackages($package->dependencies());
        $this->registeredPackages[$package::class] = $package;
        $package->initialize();

        if (!$this->mappingWasCached) {
            $this->mappingCompiler->addPackage($package);
        }
    }

    public function addPackages(array $packages): void
    {
        foreach ($packages as $package) {
            $this->addPackage($package);
        }
    }

    public function __destruct()
    {
        if (!$this->mappingWasCached) {
            // Delete the temporary initial mapping, and store the current, complete mapping
            $cache = $this->cacheManager->get();
            $cache->remove(self::SERVICE_MAPPING_CACHE_KEY);
            $cache->get(self::SERVICE_MAPPING_CACHE_KEY, fn() => $this->mapping);
        }
    }

    public function primeCaches(): void
    {
        foreach ($this->mapping->getAll() as $serviceName) {
            if ((new \ReflectionClass($serviceName))->implementsInterface(PrimesCache::class)) {
                /** @var PrimesCache $service */
                $service = $this->resolve($serviceName);
                $service->primeCache();
            }
        }
    }

    /**
     * The return value  is an object of type $type. This is specified in PhpStorm in .phpstorm.meta.php
     */
    public function resolve(string $type): object
    {
        if (null === $service = $this->findService($type)) {
            throw new Exceptions\ServiceNotFoundByTypeException($type);
        }

        if (!array_key_exists($service, $this->services)) {
            $this->services[$service] = $this->instantiator->instantiate($service);
        }

        return $this->services[$service];
    }

    public function findService(string $type): ?string
    {
        return $this->mapping->has($type) ? $this->mapping->get($type) : null;
    }

    public function primeCache(): void
    {
        //$this->mapping();
    }

    public function addParameterResolver(ParameterResolver $parameterResolver): void
    {
        $this->instantiator->addResolver($parameterResolver);
    }

    public function bindService(object $service, string ...$forTypes): void
    {
        if ($this->mappingWasCached) {
            // It's already registered
            return;
        }

        $this->services[$service::class] = $service;

        foreach ($forTypes as $forType) {
            $this->mapping->set($forType, $service::class);
        }
    }

    public function instantiate(string $className): object
    {
        return $this->instantiator->instantiate($className);
    }

    public function getServiceClassNames(): array
    {
        return $this->mapping->getAll();
    }
}
