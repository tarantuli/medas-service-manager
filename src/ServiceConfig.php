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
 * Plain, serializable configuration state produced by ServiceConfigBuilder.
 * Contains no bootstrap machinery — only the data ServiceManager needs at runtime.
 */
class ServiceConfig implements ServiceConfigInterface
{
    /** @var Registry\PrioritizedRegistry<ExceptionHandler> */
    private readonly Registry\PrioritizedRegistry $exceptionHandlerRegistry;

    public function __construct(
        private readonly string                       $objectInstantiatorClass,
        private readonly ErrorHandler                 $errorHandler,
        private readonly bool                         $isDev,
        private readonly Mapping\ServiceMapping       $mapping,
        private readonly array                        $packageClasses,
        private array                                 $exceptionHandlerClasses,
        private array                                 $manualBindings,
        private readonly Registry\PrioritizedRegistry $parameterResolverRegistry,
        private readonly Registry\PrioritizedRegistry $argumentProcessorRegistry,
    )
    {
        // ExceptionHandlers have no priority concept — insertion order is preserved.
        $this->exceptionHandlerRegistry = new Registry\PrioritizedRegistry(false);
    }

    public function __serialize(): array
    {
        $errorHandler = $this->errorHandler;

        return [
            'objectInstantiatorClass' => $this->objectInstantiatorClass,
            'errorHandlerClass' => $errorHandler::class,
            'isDev' => $this->isDev,
            'mapping' => $this->mapping,
            'packageClasses' => $this->packageClasses,
            'exceptionHandlerClasses' => $this->exceptionHandlerClasses,
            'manualBindings' => $this->manualBindings,
            'parameterResolverRegistry' => $this->parameterResolverRegistry,
            'argumentProcessorRegistry' => $this->argumentProcessorRegistry,
        ];
    }

    public function __unserialize(array $data): void
    {
        $errorHandlerClass = $data['errorHandlerClass'];
        $this->objectInstantiatorClass = $data['objectInstantiatorClass'];
        $this->errorHandler = new $errorHandlerClass();
        $this->isDev = $data['isDev'];
        $this->mapping = $data['mapping'];
        $this->packageClasses = $data['packageClasses'];
        $this->exceptionHandlerClasses = $data['exceptionHandlerClasses'];
        $this->manualBindings = $data['manualBindings'];
        $this->parameterResolverRegistry = $data['parameterResolverRegistry'];
        $this->argumentProcessorRegistry = $data['argumentProcessorRegistry'];

        // ExceptionHandlers have no priority concept — insertion order is preserved.
        $this->exceptionHandlerRegistry = new Registry\PrioritizedRegistry(false);

        $this->addParameterResolver(new Mapping\ManualBindingFinder($this));
    }

    public function isDev(): bool
    {
        return $this->isDev;
    }

    // -------------------------------------------------------------------------
    // Read-only package access
    // -------------------------------------------------------------------------
    /** @return class-string<Package>[] */
    public function packageClasses(): array
    {
        return $this->packageClasses;
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
    // Type bindings (used by ServiceManager::bindImplementation at runtime)
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
    /** @return array<string, array<string, array<string, mixed>>> */
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

    // -------------------------------------------------------------------------
    // Stub methods — not applicable on the data container, only on the builder
    // -------------------------------------------------------------------------
    public function addPackage(Package $package, bool $doInitialize = false): self
    {
        throw new \LogicException('addPackage() must be called on ServiceConfigBuilder, not ServiceConfig.');
    }

    public function addPackages(array $packages): self
    {
        throw new \LogicException('addPackages() must be called on ServiceConfigBuilder, not ServiceConfig.');
    }

    public function addDevPackage(Package $package, bool $doInitialize = false): self
    {
        throw new \LogicException('addDevPackage() must be called on ServiceConfigBuilder, not ServiceConfig.');
    }

    public function addDevPackages(array $packages): self
    {
        throw new \LogicException('addDevPackages() must be called on ServiceConfigBuilder, not ServiceConfig.');
    }
}
