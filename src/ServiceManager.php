<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\{
    Attributes\Entrypoint,
    CorePackage,
    Exceptions\MultipleImplementorsFound,
    Exceptions\ServiceNotFoundByType,
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

        foreach (medas()->serviceManager()->config()->packageClasses() as $packageClass) {
            $packageClass::instance()->postInstall();
        }
    }

    private readonly ServiceConfig $config;

    /** @var object[] */
    private array $services = [];

    private CacheManagerInterface $cacheManager;
    public readonly CachePrimer $cachePrimer;

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

    /** @var string[]|null Cached result of getServiceClassNames(); null means stale. */
    private array|null $serviceClassNamesCache = null;

    #[Entrypoint]
    public function __construct(
        \Closure                $initializer,
        CacheInterface|null     $cache = null,
        private readonly string $panicLogPath = 'var/log/service-manager-panic.log',
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

        $this->config->errorHandler()->set();
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
     * Owns the full bootstrap sequence: object instantiator, core services, package initialize() and ready().
     */
    private function buildFreshConfig(\Closure $initializer): ServiceConfig
    {
        $builder = $initializer();

        if (!$builder instanceof ServiceConfigBuilder) {
            throw new Exceptions\InitializerDidNotReturnServiceConfig($builder);
        }

        $this->configWasCached = false;

        $this->bootstrapObjectInstantiator($builder);
        $this->bootstrapCoreServices($builder);
        $this->bootstrapPackages($builder);

        return $builder->build();
    }

    /**
     * Instantiates the configured ObjectInstantiator and registers it on medas() and in the service map.
     * Also adds the type binding to the builder so it ends up in the final mapping.
     */
    private function bootstrapObjectInstantiator(ServiceConfigBuilder $builder): void
    {
        $className = $builder->objectInstantiatorClass();
        $objectInstantiator = new ($className)($this);
        medas()->setObjectInstantiator($this->services[$className] = $objectInstantiator);

        $builder->addTypeBinding($className, ObjectInstantiator::class);
    }

    /**
     * Registers core service instances (ServiceManager, CacheManager, ExceptionHandlerManager)
     * into the service map and adds their type bindings to the builder.
     */
    private function bootstrapCoreServices(ServiceConfigBuilder $builder): void
    {
        $this->services[self::class] = $this;
        $this->services[Cache\CacheManager::class] = $this->cacheManager;

        $builder->addTypeBinding(self::class, ServiceManagerInterface::class, self::class);
        $builder->addTypeBinding(Cache\CacheManager::class, Cache\CacheManager::class);

        $exceptionHandlerManager = new ErrorHandling\ExceptionHandlerManager($builder);
        $this->services[ErrorHandling\ExceptionHandlerManager::class] = $exceptionHandlerManager;

        $builder->addTypeBinding(
            ErrorHandling\ExceptionHandlerManager::class,
            ErrorHandling\ExceptionHandlerManager::class,
        );
    }

    /**
     * Calls initialize() then ready() on all registered packages, passing the builder so
     * packages can still register resolvers, processors, and type bindings before build().
     */
    private function bootstrapPackages(ServiceConfigBuilder $builder): void
    {
        foreach ($builder->packageClasses() as $packageClass) {
            $packageClass::instance()->initialize($builder);
        }

        foreach ($builder->packageClasses() as $packageClass) {
            $packageClass::instance()->ready();
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
                    error_log($exception->getMessage() . "\n", 3, $this->panicLogPath);
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
        return $this->serviceClassNamesCache ??= array_unique($this->config->mapping()->getAll());
    }

    /**
     * The return value is an object of type `$type`.
     * (Return type is specified in PhpStorm in .phpstorm.meta.php)
     */
    #[Entrypoint]
    public function resolve(string $type): object
    {
        if (null === $service = $this->findImplementingClass($type)) {
            throw new ServiceNotFoundByType($type);
        }

        if (!array_key_exists($service, $this->services)) {
            $this->services[$service] = medas()->objectInstantiator()->instantiate($service);
        }

        return $this->services[$service];
    }

    public function findImplementingClass(string $type): string|null
    {
        $mapping = $this->config->mapping();

        if ($mapping->has($type)) {
            return $mapping->get($type);
        }
        elseif ($mapping->hasMultiple($type)) {
            throw new MultipleImplementorsFound($type, $mapping->getImplementors($type));
        }

        return null;
    }

    #[Entrypoint]
    public function bindImplementation(object $implementation, string ...$forTypes): self
    {
        $this->services[$implementation::class] = $implementation;
        $this->serviceClassNamesCache = null;

        foreach ($forTypes as $forType) {
            if ($this->config->mapping()->set($forType, $implementation::class)) {
                // At least one mapping entry changed — the config must be persisted.
                $this->mappingDirty = true;
            }
        }

        return $this;
    }
}
