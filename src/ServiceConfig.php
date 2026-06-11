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

class ServiceConfig implements ServiceConfigInterface
{
    private readonly Mapping\MappingManager $mappingManager;
    private readonly Mapping\ServiceMapping $mapping;
    private readonly ErrorHandler $errorHandler;
    private readonly string $objectInstantiatorClass;
    private readonly Registry\PackageRegistry $packageRegistry;

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

    private readonly bool $isDev;

    public function __construct(
        string            $objectInstantiatorClass,
        ErrorHandler|null $errorHandler = null,
        bool              $isDev = false,
    )
    {
        // Default to the basic error handler; use the aggressive one during development.
        $this->errorHandler = $errorHandler ?? new ErrorHandling\BasicErrorHandler();
        $this->objectInstantiatorClass = $objectInstantiatorClass;
        $this->isDev = $isDev;
        $this->mappingManager = new Mapping\MappingManager();
        $this->mapping = $this->mappingManager->get();
        $this->packageRegistry = new Registry\PackageRegistry($this->mappingManager);

        // ExceptionHandlers have no priority concept — insertion order is preserved.
        $this->exceptionHandlerRegistry = new Registry\PrioritizedRegistry(false);
        $this->parameterResolverRegistry = new Registry\PrioritizedRegistry();
        $this->argumentProcessorRegistry = new Registry\PrioritizedRegistry();

        $this->addPackage(ServiceManagerPackage::instance());
        $this->addParameterResolver(new Mapping\ManualBindingFinder($this));
    }

    public function __serialize(): array
    {
        $errorHandler = $this->errorHandler;

        return [
            'objectInstantiatorClass' => $this->objectInstantiatorClass,
            'errorHandlerClass' => $errorHandler::class,
            'isDev' => $this->isDev,
            'mapping' => $this->mapping,
            'manualBindings' => $this->manualBindings,
            'packageClasses' => array_keys($this->packageRegistry->all()),
            'exceptionHandlerClasses' => array_merge(
                array_map(fn(ExceptionHandler $h) => $h::class, $this->exceptionHandlerRegistry->all()),
                $this->exceptionHandlerClasses,
            ),
            'parameterResolverRegistry' => $this->parameterResolverRegistry,
            'argumentProcessorRegistry' => $this->argumentProcessorRegistry,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->objectInstantiatorClass = $data['objectInstantiatorClass'];
        $this->isDev = $data['isDev'];
        $this->manualBindings = $data['manualBindings'];
        $this->exceptionHandlerClasses = $data['exceptionHandlerClasses'];

        // Restore the error handler by class name.
        $errorHandlerClass = $data['errorHandlerClass'];
        $this->errorHandler = new $errorHandlerClass();

        // Rebuild mapping infrastructure and re-register all packages so MappingManager
        // scans their source directories. initialize() is NOT called — that already
        // happened before caching.
        $this->mappingManager = new Mapping\MappingManager();
        $this->mapping = $this->mappingManager->get();
        $this->packageRegistry = new Registry\PackageRegistry($this->mappingManager);

        foreach ($data['packageClasses'] as $packageClass) {
            $this->packageRegistry->add($packageClass::instance(), $this);
        }

        // Re-apply manual type bindings on top of the freshly scanned mapping.
        foreach ($data['mapping']->getAll() as $type => $className) {
            $this->mapping->set($type, $className);
        }

        // Registries deserialize themselves to class names; service() resolves them lazily.
        $this->exceptionHandlerRegistry = new Registry\PrioritizedRegistry(false);
        $this->parameterResolverRegistry = $data['parameterResolverRegistry'];
        $this->argumentProcessorRegistry = $data['argumentProcessorRegistry'];

        // Re-add exception handlers by class name.
        foreach ($data['exceptionHandlerClasses'] as $class) {
            $this->exceptionHandlerRegistry->add(service($class));
        }

        $this->addParameterResolver(new Mapping\ManualBindingFinder($this));
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

    /** @return Package[] */
    public function registeredPackages(): array
    {
        return $this->packageRegistry->all();
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

    /** @return ExceptionHandler[] */
    public function exceptionHandlers(): array
    {
        return $this->exceptionHandlerRegistry->all();
    }

    public function addExceptionHandlerClasses(string ...$classes): self
    {
        array_push($this->exceptionHandlerClasses, ...$classes);

        return $this;
    }

    /** @return string[] */
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
            $this->mapping->set($type, $implementationClass);
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

    /** @return ParameterResolver[] */
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

    /** @return ArgumentProcessor[] */
    public function argumentProcessors(): array
    {
        return $this->argumentProcessorRegistry->all();
    }

    // -------------------------------------------------------------------------
    // Mapping & instantiation
    // -------------------------------------------------------------------------
    public function mapping(): Mapping\ServiceMapping
    {
        return $this->mapping;
    }

    public function objectInstantiatorClass(): string
    {
        return $this->objectInstantiatorClass;
    }

    // -------------------------------------------------------------------------
    // Manual bindings
    // -------------------------------------------------------------------------
    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function manualBindings(): array
    {
        return $this->manualBindings;
    }

    public function addManualBinding(
        string $class,
        string $parameter,
        mixed  $value,
        string $method = '__construct',
    ): void
    {
        $this->manualBindings[$class][$method][$parameter] = $value;
    }

    // -------------------------------------------------------------------------
    // Error handling
    // -------------------------------------------------------------------------
    public function errorHandler(): ErrorHandler
    {
        return $this->errorHandler;
    }
}
