<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\{
    Attributes\Entrypoint,
    CorePackage,
    Interfaces\Cache,
    Interfaces\CacheManager,
    Interfaces\ServiceManager as ServiceManagerInterface
};
use Medas\ObjectInstantiator\ObjectInstantiator;
use Medas\ServiceManager\Cache\CacheManager as CacheCacheManager;

class ServiceManager implements ServiceManagerInterface
{
    private const CONFIG_CACHE_KEY = ServiceConfig::class;

    public static function postInstall(): void
    {
        service(CacheManager::class)->clearAll();

        $registeredPackages = medas()->serviceManager()->config->registeredPackages();

        foreach ($registeredPackages as $package) {
            $package->postInstall();
        }
    }

    private readonly ServiceConfig $config;

    /** @var object[] */
    private array $services = [];

    private CacheManager $cacheManager;
    public readonly CachePrimer $cachePrimer;

    /** @var bool[] */
    private array $initializedPackages = [];

    #[Entrypoint]
    public function __construct(
        \Closure $initializer = null,
        Cache    $cache = null,
    )
    {
        CorePackage::instance()->loadGlobalFunctions();

        $this->registerThisInstance();
        $this->initializeCacheManager($cache);

        $this->cachePrimer = new CachePrimer($this, $this->cacheManager);

        $this->config = $this->cacheManager->get()->get(
            self::CONFIG_CACHE_KEY,
            fn() => $this->initializeConfig($initializer)
        );

        // This should be instantiated *after* fetching the mapping through the config
        medas()->setObjectInstantiator($this->services[ObjectInstantiator::class] = new ObjectInstantiator());

        $this->config->errorHandler()->set();
        $this->initializeYourself();
        $this->initializePackages();
    }

    private function registerThisInstance(): void
    {
        $this->services[self::class] = $this;

        medas()->setServiceManager($this);
    }

    private function initializeCacheManager(Cache|null $cache): void
    {
        $this->cacheManager = new CacheCacheManager();

        if ($cache) {
            $this->cacheManager->register($cache);
        }
    }

    private function initializeConfig(\Closure|null $initializer): ServiceConfig
    {
        $config = $initializer ? $initializer() : new ServiceConfig();

        if (!$config instanceof ServiceConfig) {
            throw new Exceptions\InitializerDidNotReturnServiceConfig($config);
        }

        $config->wasNotCached();

        return $config;
    }

    private function initializeYourself(): void
    {
        $this->bindImplementation($this->cacheManager, CacheManager::class);
    }

    private function initializePackages(): void
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
            try {
                // Explicitly set the current configuration
                $this->cacheManager->get()->set(self::CONFIG_CACHE_KEY, $this->config);
            }
            catch (\Exception) {
                // Do nothing
            }
        }
    }

    public function config(): ServiceConfig
    {
        return $this->config;
    }

    /**
     * Returns the class names of all the services that are discoverable.
     *
     * @return string[]
     */
    public function getServiceClassNames(): array
    {
        return array_unique($this->config->mapping()->getAll());
    }

    /**
     * The return value is an object of type `$type`.
     */
    /*
     * This is specified in PhpStorm in .phpstorm.meta.php
     */
    #[Entrypoint]
    public function resolve(string $type): object
    {
        if (null === $service = $this->findImplementingClass($type)) {
            throw new Exceptions\ServiceNotFoundByType($type);
        }

        if (!array_key_exists($service, $this->services)) {
            $this->services[$service] = medas()->objectInstantiator()->instantiate($service);
        }

        return $this->services[$service];
    }

    public function findImplementingClass(string $type): string|null
    {
        $mapping = $this->config->mapping();

        return $mapping->has($type) ? $mapping->get($type) : null;
    }

    #[Entrypoint]
    public function bindImplementation(object $implementation, string ...$forTypes): self
    {
        $this->services[$implementation::class] = $implementation;
        $changedSomething = false;

        foreach ($forTypes as $forType) {
            $changedSomething = $changedSomething
                || $this->config->mapping()->set($forType, $implementation::class);
        }

        if ($changedSomething) {
            // The mapping has changed and must be saved to cache
            $this->config->doSaveToCache();
        }

        return $this;
    }
}
