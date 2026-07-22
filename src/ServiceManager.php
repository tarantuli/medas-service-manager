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

    private array $activeResolves = [];
    private readonly CacheManagerInterface $cacheManager;

    #[Entrypoint]
    public function __construct(
        \Closure            $initializer,
        CacheInterface|null $cache = null,
    )
    {
        CorePackage::instance()->loadGlobalFunctions();

        $this->registerThisInstance();
        $this->initializeCacheManager($cache);

        $this->config = $this->loadConfig($initializer);

        // Register the exception handler early so that it can register the exception handler early.
        $exceptionHandlerManager = new ErrorHandling\ExceptionHandlerManager();
        $this->services[ErrorHandling\ExceptionHandlerManager::class] = $exceptionHandlerManager;

        (new $this->config->errorPolicy)->set();

        $this->instantiateObjectInstantiator();

        // Resolve the detailed exception handler after the object instantiator is ready.
        $exceptionHandlerManager->resolveHandlers($this, $this->config);

        // ready() must run on every request: the config may come from cache, but anything
        // ready() does requires live service resolution and can therefore never itself be cached.
        $this->readyPackages();
    }

    private function registerThisInstance(): void
    {
        $this->services[self::class] = $this;

        medas()->setServiceManager($this);
    }

    private function initializeCacheManager(CacheInterface|null $cache): void
    {
        $this->cacheManager = new Cache\CacheManager($this);
        $this->services[Cache\CacheManager::class] = $this->cacheManager;

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
        $this->initializePackages($builder);
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
        $builder->addTypeBinding(self::class, ServiceManagerInterface::class, self::class);
        $builder->addTypeBinding(Cache\CacheManager::class, Cache\CacheManager::class);
    }

    /**
     * Calls initialize() on all registered packages so they can register resolvers, processors,
     * and type bindings before build(). Only runs when the config is being freshly built —
     * the results become part of the cached ServiceConfig.
     */
    private function initializePackages(ServiceConfigBuilder $builder): void
    {
        foreach ($builder->packageClasses() as $packageClass) {
            $packageClass::instance()->initialize($builder);
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

        $this->services[$this->config->objectInstantiatorClass] = $objectInstantiator;

        medas()->setObjectInstantiator($objectInstantiator);
    }

    /**
     * Calls ready() on all registered packages. Runs on every request, regardless of whether
     * the config came from cache — ready() exists specifically for setup that requires live
     * service resolution and therefore cannot be expressed as cacheable config.
     */
    private function readyPackages(): void
    {
        foreach ($this->config->packageClasses as $packageClass) {
            $packageClass::instance()->ready();
        }
    }

    /**
     * Returns the class names of all discovered services.
     *
     * @return string[]
     */
    public function getServiceClassNames(): array
    {
        return $this->config->mapping->getClassNames();
    }

    /**
     * The return value is an object of type `$type`.
     * (Return type is specified in PhpStorm in .phpstorm.meta.php)
     */
    #[Entrypoint]
    public function resolve(string $type): object
    {
        $this->activeResolves[$type] = true;

        if (null === $service = $this->findImplementingClass($type)) {
            throw new ServiceNotFoundByType($type, array_keys($this->activeResolves));
        }

        if (!array_key_exists($service, $this->services)) {
            $this->services[$service] = medas()->objectInstantiator()->instantiate($service);
        }

        unset($this->activeResolves[$type]);

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
