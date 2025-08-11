<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\{Attributes\Service, Interfaces\ArgumentProcessor, Interfaces\ParameterResolver};

#[Service]
class ServiceConfig
{
    private Mapping\MappingManager $mappingManager;
    private Mapping\ServiceMapping $mapping;
    private array $manualBindings = [];
    private ErrorHandling\ErrorHandler $errorHandler;

    /** @var Package[] */
    private array $registeredPackages = [];

    /** @var ErrorHandling\ExceptionHandler[] */
    private array $exceptionHandlers = [];

    /** @var ParameterResolver[] */
    private array $parameterResolvers = [];

    /** @var ArgumentProcessor[] */
    private array $argumentProcessors = [];

    private bool $wasCached = true;
    private bool $mustBeSavedToCache = false;

    public function __construct(
        ErrorHandling\ErrorHandler $errorHandler = null,
    )
    {
        $this->errorHandler = $errorHandler ?? new ErrorHandling\BasicErrorHandler();
        $this->mappingManager = new Mapping\MappingManager();
        $this->mapping = $this->mappingManager->get();

        $this->addPackage(ServiceManagerPackage::instance());
        $this->addParameterResolver(new Mapping\ManualBindingFinder());
        $this->addExceptionHandler(ErrorHandling\CliExceptionHandler::create());
    }

    public function addPackage(Package $package, bool $doInitialize = false): self
    {
        if (array_key_exists($package::class, $this->registeredPackages)) {
            return $this;
        }

        $this->addPackages($package->dependencies());

        $this->registeredPackages[$package::class] = $package;

        $this->mappingManager->addPackage($package);

        if ($doInitialize) {
            $package->initialize($this);
        }

        return $this;
    }

    public function addPackages(array $packages): self
    {
        foreach ($packages as $package) {
            $this->addPackage($package);
        }

        return $this;
    }

    public function wasNotCached(): void
    {
        $this->wasCached = false;
    }

    public function mapping(): Mapping\ServiceMapping
    {
        return $this->mapping;
    }

    public function errorHandler(): ErrorHandling\ErrorHandler
    {
        return $this->errorHandler;
    }

    public function wasCached(): bool
    {
        return $this->wasCached;
    }

    public function doSaveToCache(): void
    {
        $this->mustBeSavedToCache = true;
    }

    public function mustBeSavedToCache(): bool
    {
        return $this->mustBeSavedToCache || (!$this->wasCached);
    }

    public function addExceptionHandler(ErrorHandling\ExceptionHandler $exceptionHandler): self
    {
        foreach ($this->exceptionHandlers as $handler) {
            if ($handler::class === $exceptionHandler::class) {
                return $this;
            }
        }

        $this->exceptionHandlers[] = $exceptionHandler;

        return $this;
    }

    public function addParameterResolver(ParameterResolver $parameterResolver): self
    {
        foreach ($this->parameterResolvers as $resolver) {
            if ($resolver::class === $parameterResolver::class) {
                return $this;
            }
        }

        $this->parameterResolvers[] = $parameterResolver;

        usort(
            $this->parameterResolvers,
            fn(ParameterResolver $a, ParameterResolver $b) => -($a->priority() <=> $b->priority())
        );

        return $this;
    }

    public function addArgumentProcessor(ArgumentProcessor $argumentProcessor): self
    {
        foreach ($this->argumentProcessors as $processor) {
            if ($processor::class === $argumentProcessor::class) {
                return $this;
            }
        }

        $this->argumentProcessors[] = $argumentProcessor;

        usort(
            $this->argumentProcessors,
            fn(ArgumentProcessor $a, ArgumentProcessor $b) => -($a->priority() <=> $b->priority())
        );

        return $this;
    }

    public function exceptionHandlers(): array
    {
        return $this->exceptionHandlers;
    }

    public function parameterResolvers(): array
    {
        return $this->parameterResolvers;
    }

    public function argumentProcessors(): array
    {
        return $this->argumentProcessors;
    }

    public function registeredPackages(): array
    {
        return $this->registeredPackages;
    }

    public function manualBindings(): array
    {
        return $this->manualBindings;
    }

    public function addManualBinding(
        string $class,
        string $parameter,
        mixed  $value,
        string $method = '__construct'
    ): void
    {
        $this->manualBindings[$class][$method][$parameter] = $value;
    }
}
