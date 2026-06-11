<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\Interfaces\{
    ArgumentProcessor,
    ErrorHandler,
    ExceptionHandler,
    Package,
    ParameterResolver,
    ServiceConfig as ServiceConfigInterface
};

/**
 * Bootstrap-time builder for ServiceConfig.
 * Holds MappingManager and PackageRegistry — the machinery needed to discover and register
 * packages. Once bootstrap is complete, call build() to get a plain, serializable ServiceConfig.
 */
class ServiceConfigBuilder implements ServiceConfigInterface
{
    private readonly Mapping\MappingManager $mappingManager;
    private readonly Registry\PackageRegistry $packageRegistry;
    private readonly ErrorHandler $errorHandler;
    private readonly string $objectInstantiatorClass;
    private readonly bool $isDev;

    /** @var Registry\PrioritizedRegistry<ExceptionHandler> */
    private readonly Registry\PrioritizedRegistry $exceptionHandlerRegistry;

    /** @var string[] */
    private array $exceptionHandlerClasses = [];

    /** @var Registry\PrioritizedRegistry<ParameterResolver> */
    private readonly Registry\PrioritizedRegistry $parameterResolverRegistry;

    /** @var Registry\PrioritizedRegistry<ArgumentProcessor> */
    private readonly Registry\PrioritizedRegistry $argumentProcessorRegistry;

    /** @var array<string, array<string, array<string, mixed>>> */
    private array $manualBindings = [];

    public function __construct(
        string            $objectInstantiatorClass,
        ErrorHandler|null $errorHandler = null,
        bool              $isDev = false,
    )
    {
        $this->objectInstantiatorClass = $objectInstantiatorClass;
        $this->errorHandler = $errorHandler ?? new ErrorHandling\BasicErrorHandler();
        $this->isDev = $isDev;
        $this->mappingManager = new Mapping\MappingManager();
        $this->packageRegistry = new Registry\PackageRegistry($this->mappingManager);

        // ExceptionHandlers have no priority concept — insertion order is preserved.
        $this->exceptionHandlerRegistry = new Registry\PrioritizedRegistry(false);
        $this->parameterResolverRegistry = new Registry\PrioritizedRegistry();
        $this->argumentProcessorRegistry = new Registry\PrioritizedRegistry();

        $this->addPackage(ServiceManagerPackage::instance());
        $this->addParameterResolver(new Mapping\ManualBindingFinder($this));
    }

    public function build(): ServiceConfig
    {
        return new ServiceConfig(
            objectInstantiatorClass: $this->objectInstantiatorClass,
            errorHandler: $this->errorHandler,
            isDev: $this->isDev,
            mapping: $this->mappingManager->get(),
            packageClasses: array_keys($this->packageRegistry->all()),
            exceptionHandlerClasses: array_merge(
                array_map(fn(ExceptionHandler $h) => $h::class, $this->exceptionHandlerRegistry->all()),
                $this->exceptionHandlerClasses,
            ),
            manualBindings: $this->manualBindings,
            parameterResolverRegistry: $this->parameterResolverRegistry,
            argumentProcessorRegistry: $this->argumentProcessorRegistry,
        );
    }

    public function objectInstantiatorClass(): string
    {
        return $this->objectInstantiatorClass;
    }

    public function isDev(): bool
    {
        return $this->isDev;
    }

    // -------------------------------------------------------------------------
    // Package management
    // -------------------------------------------------------------------------
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

    public function addDevPackage(Package $package, bool $doInitialize = false): ServiceConfigInterface
    {
        if ($this->isDev) {
            $this->addPackage($package, $doInitialize);
        }

        return $this;
    }

    public function addDevPackages(array $packages): ServiceConfigInterface
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

    // -------------------------------------------------------------------------
    // Exception handlers
    // -------------------------------------------------------------------------
    public function addExceptionHandler(ExceptionHandler $exceptionHandler): self
    {
        $this->exceptionHandlerRegistry->add($exceptionHandler);

        return $this;
    }

    public function addExceptionHandlers(...$exceptionHandlers): self
    {
        foreach ($exceptionHandlers as $exceptionHandler) {
            $this->addExceptionHandler($exceptionHandler);
        }

        return $this;
    }

    public function exceptionHandlers(): array
    {
        return $this->exceptionHandlerRegistry->all();
    }

    public function addExceptionHandlerClasses(string ...$classes): self
    {
        array_push($this->exceptionHandlerClasses, ...$classes);

        return $this;
    }

    public function exceptionHandlerClasses(): array
    {
        return $this->exceptionHandlerClasses;
    }

    // -------------------------------------------------------------------------
    // Type bindings
    // -------------------------------------------------------------------------
    public function addTypeBinding(string $implementationClass, string ...$forTypes): self
    {
        foreach ($forTypes as $type) {
            $this->mappingManager->get()->set($type, $implementationClass);
        }

        return $this;
    }

    // -------------------------------------------------------------------------
    // Parameter resolvers
    // -------------------------------------------------------------------------
    public function addParameterResolver(ParameterResolver $parameterResolver): self
    {
        $this->parameterResolverRegistry->add($parameterResolver);

        return $this;
    }

    public function addParameterResolvers(ParameterResolver ...$parameterResolvers): self
    {
        foreach ($parameterResolvers as $parameterResolver) {
            $this->addParameterResolver($parameterResolver);
        }

        return $this;
    }

    public function parameterResolvers(): array
    {
        return $this->parameterResolverRegistry->all();
    }

    // -------------------------------------------------------------------------
    // Argument processors
    // -------------------------------------------------------------------------
    public function addArgumentProcessor(ArgumentProcessor $argumentProcessor): self
    {
        $this->argumentProcessorRegistry->add($argumentProcessor);

        return $this;
    }

    public function addArgumentProcessors(ArgumentProcessor ...$argumentProcessors): self
    {
        foreach ($argumentProcessors as $argumentProcessor) {
            $this->addArgumentProcessor($argumentProcessor);
        }

        return $this;
    }

    public function argumentProcessors(): array
    {
        return $this->argumentProcessorRegistry->all();
    }

    // -------------------------------------------------------------------------
    // Manual bindings
    // -------------------------------------------------------------------------
    public function addManualBinding(
        string $class,
        string $parameter,
        mixed  $value,
        string $method = '__construct',
    ): void
    {
        $this->manualBindings[$class][$method][$parameter] = $value;
    }
}
