<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\{
    Attributes\Entrypoint,
    CorePackage,
    Interfaces\Cache as CacheInterface,
    Interfaces\CacheManager as CacheManagerInterface,
    Interfaces\ObjectInstantiator,
    Interfaces\ServiceManager as ServiceManagerInterface
};

class ServiceManager implements ServiceManagerInterface
{
    private const string CONFIG_CACHE_KEY = ServiceConfig::class;

    /**
     * This method should only be called by "composer update" or similar command line scripts.
     */
    public static function postInstall(): void
    {
        service(CacheManagerInterface::class)->clearAll();

        $registeredPackages = medas()->serviceManager()->config->registeredPackages();

        foreach ($registeredPackages as $package) {
            $package->postInstall();
        }
    }

    private readonly ServiceConfig $config;

    /** @var object[] */
    private array $services = [];

    private CacheManagerInterface $cacheManager;
    public readonly CachePrimer $cachePrimer;

    /** @var bool[] */
    private array $initializedPackages = [];

    /**
     * Whether the config was loaded from cache (true) or freshly built (false).
     * A freshly built config must always be persisted.
     */
    private bool $configWasCached = true;

    /**
     * Whether the mapping was mutated at runtime via bindImplementation().
     * When true, the config must be persisted even if it was originally cached.
     */
    private bool $mappingDirty = false;

    #[Entrypoint]
    public function __construct(
        \Closure            $initializer,
        CacheInterface|null $cache = null,
    )
    {
        CorePackage::instance()->loadGlobalFunctions();

        $this->registerThisInstance();
        $this->initializeCacheManager($cache);

        $this->cachePrimer = new CachePrimer($this, $this->cacheManager);

        $this->config = $this->cacheManager->get()->get(
            self::CONFIG_CACHE_KEY,
            fn() => $this->buildFreshConfig($initializer),
        );

        // This should be instantiated *after* fetching the mapping through the config.
        $this->initializeObjectInstantiator();
        $this->config->errorHandler()->set();
        $this->registerCoreServices();
        $this->initializePackages();
        $this->registerShutdownPersistence();
    }

    private function registerThisInstance(): void
    {
        $this->services[self::class] = $this;

        medas()->setServiceManager($this);
    }

    private function initializeCacheManager(CacheInterface|null $cache): void
    {
        $this->cacheManager = new Cache\CacheManager($this);

        if ($cache) {
            $this->cacheManager->register($cache);
        }
    }

    /**
     * Called only when there is no cached config — builds a fresh one via the user-supplied initializer closure.
     */
    private function buildFreshConfig(\Closure $initializer): ServiceConfig
    {
        $config = $initializer();

        if (!$config instanceof ServiceConfig) {
            throw new Exceptions\InitializerDidNotReturnServiceConfig($config);
        }

        $this->configWasCached = false;

        return $config;
    }

    private function initializeObjectInstantiator(): void
    {
        $className = $this->config->objectInstantiatorClass();
        $objectInstantiator = new ($className)($this);
        medas()->setObjectInstantiator($this->services[$className] = $objectInstantiator);

        $this->config->mapping()->set(ObjectInstantiator::class, $className);
    }

    private function registerCoreServices(): void
    {
        $this->bindImplementation($this, ServiceManagerInterface::class, self::class);
        $this->bindImplementation($this->cacheManager, Cache\CacheManager::class);

        $exceptionHandlerManager = new ErrorHandling\ExceptionHandlerManager($this->config);

        $this->bindImplementation(
            $exceptionHandlerManager,
            ErrorHandling\ExceptionHandlerManager::class
        );
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

    /**
     * Registers a shutdown function to persist the config to cache when required.
     *
     * This is more reliable than __destruct() because shutdown functions run at a well-known
     * point in the request lifecycle, before the GC tears down the object graph. A
     * destructor offers no ordering guarantees — the CacheManager could be destroyed first,
     * causing a silent error_log fallback that is hard to diagnose.
     */
    private function registerShutdownPersistence(): void
    {
        register_shutdown_function(function (): void {
            if (!$this->configWasCached || $this->mappingDirty) {
                try {
                    $this->cacheManager->get()->set(self::CONFIG_CACHE_KEY, $this->config);
                }
                catch (\Exception $exception) {
                    // A relative path is no problem; during bootstrap the working directory is set to the project root.
                    error_log(
                        $exception->getMessage() . "\n",
                        3,
                        'var/logs/service-manager-panic.log'
                    );
                }
            }
        });
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------
    public function config(): ServiceConfig
    {
        return $this->config;
    }

    /**
     * Returns the class names of all discoverable services.
     *
     * @return string[]
     */
    public function getServiceClassNames(): array
    {
        return array_unique($this->config->mapping()->getAll());
    }

    /**
     * The return value is an object of type `$type`.
     * (Return type is specified in PhpStorm in .phpstorm.meta.php)
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

        foreach ($forTypes as $forType) {
            if ($this->config->mapping()->set($forType, $implementation::class)) {
                // At least one mapping entry changed — the config must be persisted.
                $this->mappingDirty = true;
            }
        }

        return $this;
    }
}
