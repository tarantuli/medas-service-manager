<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\Interfaces\{Cache, PrimesCache};
use Medas\ServiceManager\Cache\{CacheManager};
use Medas\ServiceManager\Exceptions\InitializerDidNotReturnServiceConfig;
use Medas\ServiceManager\Exceptions\NoServiceManagerInstanceFound;

class ServiceManager implements PrimesCache
{
    private const CONFIG_CACHE_KEY = ServiceConfig::class;

    private static self|null $instance;

    public static function postInstall(): void
    {
        if (!self::$instance) {
            throw new NoServiceManagerInstanceFound();
        }

        service(CacheManager::class)->clearAll();

        foreach (self::$instance->config->registeredPackages() as $package) {
            $package->postInstall();
        }
    }

    private readonly ServiceConfig $config;
    /** @var object[] */
    private array $services = [];
    private CacheManager $cacheManager;
    private ServiceInstantiator $instantiator;
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
        $this->instantiator
            = $this->services[ServiceInstantiator::class]
            = new ServiceInstantiator();

        $this->config->errorHandler()->set();
        $this->initializePackages();
    }

    private function registerThisInstance(): void
    {
        $this->services[self::class]
            = self::$instance
            = $this;
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

    public static function get(): self
    {
        return self::$instance;
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
        if (null === $service = $this->findService($type)) {
            throw new Exceptions\ServiceNotFoundByType($type);
        }

        if (!array_key_exists($service, $this->services)) {
            $this->services[$service] = $this->instantiate($service);
        }

        return $this->services[$service];
    }

    public function findService(string $type): string|null
    {
        $mapping = $this->config->mapping();

        return $mapping->has($type) ? $mapping->get($type) : null;
    }

    public function instantiate(string $className, array $arguments = []): object
    {
        return $this->instantiator->instantiate($className, $arguments);
    }

    public function primeCache(): void
    {
        // Do nothing
        // TODO: Why does this not prime caches?
    }

    public function bindService(object $service, string ...$forTypes): self
    {
        $this->services[$service::class] = $service;

        $changedSomething = false;
        foreach ($forTypes as $forType) {
            $changedSomething = $changedSomething || $this->config->mapping()->set($forType, $service::class);
        }

        if ($changedSomething) {
            // The mapping has changed and must be saved to cache
            $this->config->doSaveToCache();
        }

        return $this;
    }
}
