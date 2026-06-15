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

    public readonly ServiceConfig $config;

    /** @var object[] */
    private array $services = [];

    private readonly CacheManagerInterface $cacheManager;
    public readonly CachePrimer $cachePrimer;

    /** @var string[]|null Cached result of getServiceClassNames(); null means stale. */
    private array|null $serviceClassNamesCache = null;

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
        $this->config = $this->loadConfig($initializer);

        // Register the exception handler early so that it can register the exception handler early.
        $exceptionHandlerManager = new ErrorHandling\ExceptionHandlerManager();
        $this->services[ErrorHandling\ExceptionHandlerManager::class] = $exceptionHandlerManager;
        $errorHandler = new $this->config->errorHandler;

        $errorHandler->set();

        $this->instantiateObjectInstantiator();

        // Resolve the detailed exception handler after the object instantiator is ready.
        $exceptionHandlerManager->resolveHandlers($this, $this->config);
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

    private function loadConfig(\Closure $initializer): ServiceConfig
    {
        return $this->cacheManager->get()->get(
            self::CONFIG_CACHE_KEY,
            fn() => $this->buildFreshConfig($initializer),
        );
    }

    /**
     * Called only when there is no cached config — builds a fresh one via the user-supplied initializer closure.
     * Owns the full bootstrap sequence: object instantiator, core services, package initialize() and ready().
     */
    private function buildFreshConfig(\Closure $initializer): ServiceConfig
    {
        $builder = $initializer();

        if (!$builder instanceof ServiceConfigBuilder) {
            throw new Exceptions\InitializerDidNotReturnServiceConfigBuilder($builder);
        }

        $this->bootstrapCoreServices($builder);
        $this->bootstrapPackages($builder);
        $this->bootstrapObjectInstantiator($builder);

        return $builder->build();
    }

    /**
     * Registers core service instances (ServiceManager, CacheManager, ExceptionHandlerManager)
     * into the service map and adds their type bindings to the builder.
     */
    private function bootstrapCoreServices(ServiceConfigBuilder $builder): void
    {
        $builder->addPackage(ServiceManagerPackage::instance());
        $builder->addParameterResolver(Mapping\ManualBindingFinder::class);

        $this->services[self::class] = $this;
        $this->services[Cache\CacheManager::class] = $this->cacheManager;

        $builder->addTypeBinding(self::class, ServiceManagerInterface::class, self::class);
        $builder->addTypeBinding(Cache\CacheManager::class, Cache\CacheManager::class);
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
     * Instantiates the configured ObjectInstantiator and registers it on medas() and in the service map.
     * Also adds the type binding to the builder so it ends up in the final mapping.
     */
    private function bootstrapObjectInstantiator(ServiceConfigBuilder $builder): void
    {
        $className = $builder->objectInstantiatorClass;

        $builder->addTypeBinding($className, ObjectInstantiator::class);
    }

    private function instantiateObjectInstantiator(): void
    {
        $objectInstantiator = new ($this->config->objectInstantiatorClass)(
            $this->config->parameterResolvers,
            $this->config->argumentProcessors
        );

        medas()->setObjectInstantiator($this->services[$this->config->objectInstantiatorClass] = $objectInstantiator);
    }

    /**
     * Returns the class names of all discoverable services.
     *
     * @return string[]
     */
    public function getServiceClassNames(): array
    {
        return $this->serviceClassNamesCache ??= array_unique($this->config->mapping->getAll());
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
        $mapping = $this->config->mapping;

        if ($mapping->has($type)) {
            return $mapping->get($type);
        }
        elseif ($mapping->hasMultiple($type)) {
            throw new MultipleImplementorsFound($type, $mapping->getImplementors($type));
        }

        return null;
    }
}
