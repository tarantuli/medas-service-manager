<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\ServiceManager\Cache\CacheManager;
use Medas\ServiceManager\Interfaces\Cache;
use Medas\ServiceManager\Interfaces\Package;
use Medas\ServiceManager\Interfaces\PrimesCache;

class ServiceManager implements PrimesCache
{
    private static self $instance;

    public static function postInstall(): void
    {
        service(CacheManager::class)->clearAll();

        foreach (self::get()->registeredPackages as $package) {
            $package->postInstall();
        }
    }

    public static function get(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /** @var object[] */
    private array $services = [];
    private ServiceMapping $mapping;
    /** @var string[] */
    private array $unloadedSourceDirectories = [];
    private CacheManager $cacheManager;
    private ServiceInstantiator $instantiator;
    /** @var Package[] */
    private array $registeredPackages = [];
    private ServiceFinder $serviceFinder;

    private function __construct()
    {
        $this->services[self::class] = $this;
        $this->mapping = new ServiceMapping();
        $this->cacheManager = new CacheManager($this);
        $this->instantiator = new ServiceInstantiator($this);
        $this->serviceFinder = new ServiceFinder();
        $this->declareGlobalFunctions();

        // Register itself
        $this->addPackage(ServiceManagerPackage::instance());
    }

    private function declareGlobalFunctions(): void
    {
        require_once 'GlobalFunctions.php';
    }

    public function addPackage(Package $package, bool $analyseImmediately = true): void
    {
        if (array_key_exists($package::class, $this->registeredPackages)) {
            return;
        }

        $this->unloadedSourceDirectories[] = $package->sourceDirectory();

        $this->addPackages($package->dependencies(), false);

        if ($analyseImmediately) {
            $this->analyseSources();
        }

        $this->registeredPackages[$package::class] = $package;
        $package->initialize();
    }

    public function addPackages(array $packages, bool $analyseImmediately = true): void
    {
        foreach ($packages as $package) {
            $this->addPackage(package: $package, analyseImmediately: false);
        }

        if ($analyseImmediately) {
            $this->analyseSources();
        }
    }

    private function analyseSources(): void
    {
        foreach ($this->unloadedSourceDirectories as $directory) {
            $this->mapping->add($this->serviceFinder->find($directory));
        }

        $this->unloadedSourceDirectories = [];
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
        if ($this->mapping->has($type)) {
            return $this->mapping->get($type);
        }

        $this->analyseSources();

        return $this->mapping->has($type) ? $this->mapping->get($type) : null;
    }

    public function primeCache(): void
    {
        foreach ($this->registeredPackages as $package) {
            $this->serviceFinder->find($package->sourceDirectory());
        }
    }

    public function setCache(?Cache $cache, bool $bindAsWell = true): self
    {
        $this->cacheManager->register($cache);
        $this->serviceFinder->setCache($cache);

        if ($bindAsWell) {
            $this->bindService($cache, Cache::class);
        }

        return $this;
    }

    public function bindService(object $service, string ...$forTypes): void
    {
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
