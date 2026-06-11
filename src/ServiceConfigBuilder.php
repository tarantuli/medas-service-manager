<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\Interfaces\{
    ErrorHandler,
    Package,
    ServiceConfigBuilder as ServiceConfigBuilderInterface
};

/**
 * Bootstrap-time builder for ServiceConfig.
 * Holds MappingManager and PackageRegistry — the machinery needed to discover and register
 * packages. Once bootstrap is complete, call build() to get a plain, serializable ServiceConfig.
 */
class ServiceConfigBuilder implements ServiceConfigBuilderInterface
{
    private readonly Mapping\MappingManager $mappingManager;
    private readonly Registry\PackageRegistry $packageRegistry;
    private readonly ErrorHandler $errorHandler;
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
    }

    public function build(): ServiceConfig
    {
        $errorHandler = $this->errorHandler;
        $prioritySorter = new Registry\PrioritySorter();

        return new ServiceConfig(
            objectInstantiatorClass: $this->objectInstantiatorClass,
            errorHandler: $errorHandler::class,
            isDev: $this->isDev,
            mapping: $this->mappingManager->get(),
            packageClasses: array_keys($this->packageRegistry->all()),
            manualBindings: $this->manualBindings,
            parameterResolvers: $prioritySorter->sort($this->parameterResolvers),
            argumentProcessors: $prioritySorter->sort($this->argumentProcessors),
            exceptionHandlers: $this->exceptionHandlers,
        );
    }

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

    public function addExceptionHandlerClasses(string ...$classes): self
    {
        array_push($this->exceptionHandlers, ...$classes);

        return $this;
    }

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
