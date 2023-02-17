<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\ServiceManager\Cache\{CacheManager, Interfaces\Cache, Interfaces\PrimesCache};
use Medas\ServiceManager\Exceptions\InitializerDidNotReturnServiceConfig;

class ServiceManager implements PrimesCache
{
    private const CONFIG_CACHE_KEY = ServiceConfig::class;

    private static self|null $instance;

    public static function postInstall(): void
    {
        service(CacheManager::class)->clearAll();

        foreach (self::get()->config->registeredPackages() as $package) {
            $package->postInstall();
        }
    }

    public static function get(): self
    {
        return self::$instance;
    }

    public static function destroy(): void
    {
        self::$instance = null;
    }

    private readonly ServiceConfig $config;

    /** @var object[] */
    private array $services = [];

    private CacheManager $cacheManager;
    private ServiceInstantiator $instantiator;

    public function __construct(
        Cache    $cache = null,
        \Closure $initializer = null,
    )
    {
        $this->services[self::class]
            = self::$instance
            = $this;

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

    public function __destruct()
    {
        if ($this->config->mustBeSavedToCache()) {
            // Delete any residual cached mapping, and store the current, complete mapping
            $cache = $this->cacheManager->get();
            $cache->remove(self::CONFIG_CACHE_KEY);
            $cache->get(self::CONFIG_CACHE_KEY, fn() => $this->config);
        }
    }

    public function getServiceClassNames(): array
    {
        return $this->config->mapping()->getAll();
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

    public function config(): ServiceConfig
    {
        return $this->config;
    }
}
