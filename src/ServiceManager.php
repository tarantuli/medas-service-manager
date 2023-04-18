<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\GlobalRepository;
use Medas\Core\Interfaces\{Cache, PrimesCache};
use Medas\ServiceManager\Cache\{CacheManager};
use Medas\ServiceManager\Exceptions\{InitializerDidNotReturnServiceConfig, NoServiceManagerInstanceFound};

class ServiceManager implements \Medas\Core\Interfaces\ServiceManager, PrimesCache
{
    private const CONFIG_CACHE_KEY = ServiceConfig::class;

    public static function postInstall(): void
    {
        $instance = GlobalRepository::serviceManager();

        if (!$instance) {
            throw new NoServiceManagerInstanceFound();
        }

        service(CacheManager::class)->clearAll();

        foreach ($instance->config->registeredPackages() as $package) {
            $package->postInstall();
        }
    }

    private readonly ServiceConfig $config;

    /** @var object[] */
    private array $services = [];

    private CacheManager $cacheManager;

    /** @var bool[] */
    private array $initializedPackages = [];

    public function __construct(
        \Closure $initializer = null,
        Cache    $cache = null,
    )
    {
        $this->registerThisInstance();
        $this->initializeCacheManager($cache);

        $this->config = $this->cacheManager->get()->get(
            self::CONFIG_CACHE_KEY,
            fn() => $this->initializeConfig($initializer)
        );

        // This should be instantiated *after* fetching the mapping through the config
        GlobalRepository::setObjectInstantiator(
            $this->services[ServiceInstantiator::class]
                = new ServiceInstantiator()
        );

        $this->config->errorHandler()->set();
        $this->initializePackages();
    }

    private function registerThisInstance(): void
    {
        $this->services[self::class]
            = $this;

        GlobalRepository::setServiceManager($this);
    }

    private function initializeCacheManager(Cache|null $cache): void
    {
        $this->cacheManager
            = $this->services[CacheManager::class]
            = new CacheManager();

        if ($cache) {
            $this->cacheManager->register($cache);
        }
    }

    private function initializeConfig(\Closure|null $initializer): ServiceConfig
    {
        $config = $initializer ? $initializer() : new ServiceConfig();

        if (!$config instanceof ServiceConfig) {
            throw new InitializerDidNotReturnServiceConfig($config);
        }

        $config->wasNotCached();

        return $config;
    }

    public function initializePackages(): void
    {
        foreach ($this->config->registeredPackages() as $package) {
            if (array_key_exists($package::class, $this->initializedPackages)) {
                continue;
            }

            $package->initialize($this->config);
            $this->initializedPackages[$package::class] = true;
        }
    }

    public function __destruct()
    {
        if ($this->config->mustBeSavedToCache()) {
            // Explicitly set the current configuration
            $this->cacheManager->get()->set(self::CONFIG_CACHE_KEY, $this->config);
        }
    }

    public function config(): ServiceConfig
    {
        return $this->config;
    }

    public function primeCaches(): void
    {
        foreach ($this->getServiceClassNames() as $serviceName) {
            if ((new \ReflectionClass($serviceName))->implementsInterface(PrimesCache::class)) {
                /** @var PrimesCache $service */
                $service = $this->resolve($serviceName);
                $service->primeCache();
            }
        }
    }

    public function getServiceClassNames(): array
    {
        return array_unique($this->config->mapping()->getAll());
    }

    /**
     * The return value  is an object of type $type. This is specified in PhpStorm in .phpstorm.meta.php
     */
    public function resolve(string $type): object
    {
        if (null === $service = $this->findImplementingClass($type)) {
            throw new Exceptions\ServiceNotFoundByType($type);
        }

        if (!array_key_exists($service, $this->services)) {
            $this->services[$service] = GlobalRepository::objectInstantiator()->instantiate($service);
        }

        return $this->services[$service];
    }

    public function findImplementingClass(string $type): string|null
    {
        $mapping = $this->config->mapping();

        return $mapping->has($type) ? $mapping->get($type) : null;
    }

    public function primeCache(): void
    {
        // Do nothing
        // TODO: Why does this not prime caches?
    }

    public function bindImplementation(object $implementation, string ...$forTypes): self
    {
        $this->services[$implementation::class] = $implementation;

        $changedSomething = false;
        foreach ($forTypes as $forType) {
            $changedSomething = $changedSomething || $this->config->mapping()->set($forType, $implementation::class);
        }

        if ($changedSomething) {
            // The mapping has changed and must be saved to cache
            $this->config->doSaveToCache();
        }

        return $this;
    }
}
