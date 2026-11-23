<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\Interfaces\{
    ErrorPolicy,
    Package,
    ServiceConfigBuilder as ServiceConfigBuilderInterface
};

/**
 * Collects the configuration during bootstrap: packages and their services, type bindings, manual bindings,
 * parameter resolvers, argument processors and exception handlers. `ServiceManager` calls `build()` to turn it into
 * a plain, serializable `ServiceConfig`.
 *
 * @assumes It's only used while the config is built: in the initializer closure given to `ServiceManager`, and in
 *          `Package::initialize()`. Neither runs when the config comes from the cache, so everything configured here
 *          ends up in the cached config.
 * @sideEffect Adding a package loads every class file in it, and in the packages it depends on, to discover their
 *             services.
 * @see ServiceManager
 */
class ServiceConfigBuilder implements ServiceConfigBuilderInterface
{
    private readonly Mapping\MappingManager $mappingManager;
    private readonly Registry\PackageRegistry $packageRegistry;
    private readonly ErrorPolicy $errorPolicy;
    public readonly string $objectInstantiatorClass;
    public readonly bool $isDev;

    /** @var string[] */
    private array $exceptionHandlers = [];

    /** @var string[] */
    private array $parameterResolvers = [];

    /** @var string[] */
    private array $argumentProcessors = [];

    /** @var array<string, array<string, array<string, mixed>>> */
    private array $manualBindings = [];

    /**
     * @param ErrorPolicy|null $errorPolicy Defaults to `BasicErrorPolicy`.
     * @param bool             $isDev       Whether dev packages and dev dependencies are added.
     */
    public function __construct(
        string           $objectInstantiatorClass,
        ErrorPolicy|null $errorPolicy = null,
        bool             $isDev = false,
    )
    {
        $this->objectInstantiatorClass = $objectInstantiatorClass;
        $this->errorPolicy = $errorPolicy ?? new ErrorHandling\BasicErrorPolicy();
        $this->isDev = $isDev;
        $this->mappingManager = new Mapping\MappingManager();
        $this->packageRegistry = new Registry\PackageRegistry($this->mappingManager);
    }

    public function build(): ServiceConfig
    {
        return new ServiceConfig(
            objectInstantiatorClass: $this->objectInstantiatorClass,
            errorPolicy: $this->errorPolicy::class,
            isDev: $this->isDev,
            mapping: $this->mappingManager->get(),
            packageClasses: array_keys($this->packageRegistry->all()),
            manualBindings: $this->manualBindings,
            parameterResolvers: $this->parameterResolvers,
            argumentProcessors: $this->argumentProcessors,
            exceptionHandlers: $this->exceptionHandlers,
        );
    }

    /**
     * Adds the package, after the packages it depends on, and discovers their services.
     *
     * @param bool $doInitialize Calls the package's `initialize()` right away. Packages added in the initializer
     *                           closure don't need it: `ServiceManager` initializes all of them once the closure
     *                           returns.
     */
    public function addPackage(Package $package, bool $doInitialize = false): self
    {
        $this->packageRegistry->add($package, $this, $doInitialize);

        return $this;
    }

    public function addPackages(array $packages): self
    {
        $this->packageRegistry->addMultiple($packages, $this);

        return $this;
    }

    /**
     * Like `addPackage()`, but only when `$isDev` is set.
     */
    public function addDevPackage(Package $package, bool $doInitialize = false): ServiceConfigBuilderInterface
    {
        if ($this->isDev) {
            $this->addPackage($package, $doInitialize);
        }

        return $this;
    }

    public function addDevPackages(array $packages): ServiceConfigBuilderInterface
    {
        if ($this->isDev) {
            $this->addPackages($packages);
        }

        return $this;
    }

    /** @return class-string<Package>[] */
    public function packageClasses(): array
    {
        return array_keys($this->packageRegistry->all());
    }

    /**
     * @param string $exceptionHandler The class of an `ExceptionHandler`. Handlers are resolved when the first
     *                                 exception arrives, and every handler gets every exception.
     */
    public function addExceptionHandler(string $exceptionHandler): self
    {
        $this->exceptionHandlers[] = $exceptionHandler;

        return $this;
    }

    public function addExceptionHandlers(string ...$exceptionHandlers): self
    {
        foreach ($exceptionHandlers as $exceptionHandler) {
            $this->addExceptionHandler($exceptionHandler);
        }

        return $this;
    }

    /**
     * Makes every type in `$forTypes` resolve to `$implementationClass`. A binding wins over discovered services, and
     * `$implementationClass` is included when `ImplementorFinder` lists the implementors of those types.
     */
    public function addTypeBinding(string $implementationClass, string ...$forTypes): self
    {
        foreach ($forTypes as $type) {
            $this->mappingManager->get()->set($type, $implementationClass);
        }

        return $this;
    }

    public function addParameterResolver(string $parameterResolver): self
    {
        $this->parameterResolvers[] = $parameterResolver;

        return $this;
    }

    public function addParameterResolvers(string ...$parameterResolvers): self
    {
        foreach ($parameterResolvers as $parameterResolver) {
            $this->addParameterResolver($parameterResolver);
        }

        return $this;
    }

    public function addArgumentProcessor(string $argumentProcessor): self
    {
        $this->argumentProcessors[] = $argumentProcessor;

        return $this;
    }

    public function addArgumentProcessors(string ...$argumentProcessors): self
    {
        foreach ($argumentProcessors as $argumentProcessor) {
            $this->addArgumentProcessor($argumentProcessor);
        }

        return $this;
    }

    /**
     * Binds a value to one parameter, for `ManualBindingFinder` to supply when the class is instantiated or the
     * method is called.
     *
     * @param string $class The class that declares the method. For a constructor inherited from a parent class,
     *                      that's the parent class.
     */
    public function addManualBinding(
        string $class,
        string $parameter,
        mixed  $value,
        string $method = '__construct',
    ): self
    {
        $this->manualBindings[$class][$method][$parameter] = $value;

        return $this;
    }

    /**
     * Adds the test packages listed in an environment variable, as a comma-separated list of package classes. Class
     * names that don't exist are skipped silently, and nothing happens when the variable isn't set.
     *
     * Call it in the initialiser closure:
     *
     * ```php
     * $sm = new ServiceManager(function () use ($isDev): ServiceConfigBuilder {
     *     $config = new ServiceConfigBuilder(ObjectInstantiator::class, isDev: $isDev);
     *
     *     $config->addPackage(BackendPackage::instance());
     *     $config->addTestPackages('TEST_PACKAGES');
     *
     *     return $config;
     * });
     * ```
     *
     * @param string $envName The name of the environment variable.
     */
    public function addTestPackages(string $envName): void
    {
        $testPackages = getenv($envName);

        if ($testPackages === false) {
            return;
        }

        foreach (explode(',', $testPackages) as $testPackage) {
            if (!class_exists($testPackage)) {
                continue;
            }

            /** @var class-string<Package> $testPackage */
            /** @var Package $instance */
            $instance = $testPackage::instance();

            $this->addPackage($instance);
        }
    }
}
