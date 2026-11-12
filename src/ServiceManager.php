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
    Interfaces\InstantiatesLazily,
    Interfaces\ObjectInstantiator,
    Interfaces\ResolvesLazily,
    Interfaces\ServiceManager as ServiceManagerInterface
};

/**
 * The dependency injection container: builds the service configuration during bootstrap, and resolves services by
 * type for the rest of the process.
 *
 * Bootstrap happens in the constructor:
 *
 * 1. It registers itself, and a `CacheManager` with the given cache as `'default'`.
 * 2. It loads the `ServiceConfig` from that cache. Only when there's none, it calls the initialiser, which returns a
 *    configured `ServiceConfigBuilder`, and then `initialize()` on every package.
 * 3. It installs the exception handler and the error policy, and creates the object instantiator.
 * 4. It calls `ready()` on every package. That happens every time, whether the config was cached or not.
 *
 * @assumes There's one instance per process. It registers itself through `medas()`, which is where `sm()` and
 *          `service()` find it.
 * @sideEffect The constructor replaces the global exception handler and error handler, and sets
 *             `error_reporting()`.
 * @state Keeps every service it instantiates for the lifetime of the process, so there's one instance of each.
 * @cache The whole `ServiceConfig` is cached in the `'default'` cache. Changes to packages, services, bindings,
 *        parameter resolvers or argument processors only take effect after the caches are cleared.
 * @see ServiceConfigBuilder
 */
class ServiceManager implements ServiceManagerInterface, ResolvesLazily
{
    private const string CONFIG_CACHE_KEY = ServiceConfig::class;

    public readonly ServiceConfig $config;

    /** @var object[] */
    private array $services = [];

    /** @var object[] Already-resolved services, by the type that was asked for. */
    private array $resolvedByType = [];

    private array $activeResolves = [];
    private readonly CacheManagerInterface $cacheManager;

    /**
     * @param \Closure            $initializer Returns a configured `ServiceConfigBuilder`. Only called when there's no
     *                                         cached config.
     * @param CacheInterface|null $cache       Registered as the `'default'` cache, which also holds the config. Without
     *                                         it, the config is built again in every process.
     *
     * @throws Exceptions\InitializerDidNotReturnServiceConfigBuilder When the initializer returns something else.
     */
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

        // Install the exception handler now; its detailed handlers can only be resolved once the object
        // instantiator is ready, below
        $exceptionHandlerManager = new ErrorHandling\ExceptionHandlerManager();
        $this->services[ErrorHandling\ExceptionHandlerManager::class] = $exceptionHandlerManager;

        (new $this->config->errorPolicy)->set();

        $this->instantiateObjectInstantiator();

        // The detailed exception handlers can only be resolved once the object instantiator
        // is ready; they are resolved when the first exception arrives.
        $exceptionHandlerManager->prepareHandlers($this, $this->config);

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
     * Called only when there is no cached config — builds a fresh one via the user-supplied initialiser closure.
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
     * Calls initialize() on all registered packages so they can register resolvers, processors
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
            $this->config->argumentProcessors,
            $this->config->isDev,
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
     * Returns the service for `$type`, and instantiates it the first time. Every later call for the same type returns
     * the same instance.
     *
     * The return value is an object of type `$type`, which PhpStorm knows from `.phpstorm.meta.php`.
     *
     * @throws ServiceNotFoundByType When no service provides `$type`.
     * @throws MultipleImplementorsFound When several services provide `$type`, and no type binding decides between
     *                                   them.
     */
    #[Entrypoint]
    public function resolve(string $type): object
    {
        // Asked for before: the answer cannot change, because the mapping is fixed once
        // the config is built and a service instance is never replaced. resolve() runs
        // tens of thousands of times per request (every dispatch() and cache() goes
        // through it), so this path must stay a single array lookup.
        if (isset($this->resolvedByType[$type])) {
            return $this->resolvedByType[$type];
        }

        return $this->resolveUnresolved($type, false);
    }

    /**
     * Used for injecting dependencies: a service that hasn't been instantiated yet is registered and returned as a
     * lazy object, so it (and its own dependencies) are only instantiated when the service is actually used. Later
     * calls to `resolve()` return that same lazy object.
     *
     * Without an object instantiator that implements `InstantiatesLazily`, the service is instantiated right away.
     *
     * @throws ServiceNotFoundByType When no service provides `$type`.
     * @throws MultipleImplementorsFound When several services provide `$type`, and no type binding decides between
     *                                   them.
     */
    #[Entrypoint]
    public function resolveLazy(string $type): object
    {
        if (isset($this->resolvedByType[$type])) {
            return $this->resolvedByType[$type];
        }

        return $this->resolveUnresolved($type, true);
    }

    private function resolveUnresolved(string $type, bool $lazy): object
    {
        $this->activeResolves[$type] = true;

        // The resolve chain only lists types that are still being resolved, also after a failed resolve
        try {
            if (null === $service = $this->findImplementingClass($type)) {
                throw new ServiceNotFoundByType($type, array_keys($this->activeResolves));
            }

            if (!array_key_exists($service, $this->services)) {
                $objectInstantiator = medas()->objectInstantiator();

                $this->services[$service] = $lazy && $objectInstantiator instanceof InstantiatesLazily
                    ? $objectInstantiator->instantiateLazy($service)
                    : $objectInstantiator->instantiate($service);
            }
        }

        finally{
            unset($this->activeResolves[$type]);
        }

        return $this->resolvedByType[$type] = $this->services[$service];
    }

    /**
     * Returns the class that `$type` resolves to, without instantiating it, or `null` when no service provides it.
     *
     * @throws MultipleImplementorsFound When several services provide `$type`, and no type binding decides between
     *                                   them.
     */
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
