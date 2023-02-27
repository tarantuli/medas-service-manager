<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\ErrorHandling\{BasicErrorHandler, ErrorHandler};
use Medas\ServiceManager\Interfaces\Package;
use Medas\ServiceManager\Mapping\{MappingManager, ServiceMapping};
use Medas\ServiceManager\ParameterResolving\{ArgumentProcessor, ParameterResolver};

#[Service]
class ServiceConfig
{
    private MappingManager $mappingManager;
    private ServiceMapping $mapping;
    private ErrorHandler $errorHandler;

    /** @var Package[] */
    private array $registeredPackages = [];

    /** @var bool[] */
    private array $initializedPackages = [];

    /** @var ParameterResolver[] */
    private array $parameterResolvers = [];

    /** @var ArgumentProcessor[] */
    private array $argumentProcessors = [];

    private bool $wasCached = true;
    private bool $mustBeSavedToCache = false;

    public function __construct(
        ErrorHandler $errorHandler = null,
    )
    {
        $this->errorHandler = $errorHandler ?? new BasicErrorHandler();
        $this->mappingManager = new MappingManager();
        $this->mapping = $this->mappingManager->get();

        $this->addPackage(ServiceManagerPackage::instance());
    }

    public function wasNotCached(): void
    {
        $this->wasCached = false;
    }

    public function mapping(): ServiceMapping
    {
        return $this->mapping;
    }

    public function errorHandler(): ErrorHandler
    {
        return $this->errorHandler;
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

    public function initializePackages(): void
    {
        foreach ($this->registeredPackages as $package) {
            if (array_key_exists($package::class, $this->initializedPackages)) {
                continue;
            }

            $package->initialize($this);
            $this->initializedPackages[$package::class] = true;
        }
    }

    public function addPackages(array $packages): self
    {
        foreach ($packages as $package) {
            $this->addPackage($package);
        }

        return $this;
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

    public function addParameterResolver(ParameterResolver $parameterResolver): self
    {
        $this->parameterResolvers[] = $parameterResolver;

        usort($this->parameterResolvers,
            fn(ParameterResolver $a, ParameterResolver $b) => -($a->priority() <=> $b->priority()));

        return $this;
    }

    public function addArgumentProcessor(ArgumentProcessor $argumentProcessor): self
    {
        $this->argumentProcessors[] = $argumentProcessor;

        usort($this->argumentProcessors,
            fn(ArgumentProcessor $a, ArgumentProcessor $b) => -($a->priority() <=> $b->priority()));

        return $this;
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
}
