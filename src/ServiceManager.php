<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\ServiceManager\Interfaces\Cache;
use Medas\ServiceManager\Interfaces\Package;

class ServiceManager
{
    private static self $instance;

    public static function postComposerInstall(): void
    {
        foreach (self::get()->registeredPackages as $package) {
            $package->postComposerInstall();
        }
    }

    public static function get(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
    /** @var object[] */
    private array $services = [];
    private ServiceMapping $mapping;
    /** @var string[] */
    private array $unloadedSourceDirectories = [];
    private ServiceInstantiator $instantiator;
    /** @var Package[] */
    private array $registeredPackages = [];
    private ServiceFinder $serviceFinder;

    private function __construct()
    {
        $this->services[self::class] = $this;
        $this->mapping = new ServiceMapping();
        $this->instantiator = new ServiceInstantiator($this);
        $this->serviceFinder = new ServiceFinder();
        $this->declareGlobalFunctions();

        // Register itself
        $this->addPackage(ServiceManagerPackage::instance());
    }

    private function declareGlobalFunctions(): void
    {
        require_once 'GlobalFunctions.php';
    }

    public function addPackage(Package $package, bool $analyseImmediately = true): void
    {
        if (array_key_exists($package::class, $this->registeredPackages)) {
            return;
        }

        $this->unloadedSourceDirectories[] = $package->sourceDirectory();

        $this->addPackages($package->dependencies(), false);

        if ($analyseImmediately) {
            $this->analyseSources();
        }

        $this->registeredPackages[$package::class] = $package;
        $package->initialize();
    }

    public function addPackages(array $packages, bool $analyseImmediately = true): void
    {
        foreach ($packages as $package) {
            $this->addPackage(package: $package, analyseImmediately: false);
        }

        if ($analyseImmediately) {
            $this->analyseSources();
        }
    }

    private function analyseSources(): void
    {
        foreach ($this->unloadedSourceDirectories as $directory) {
            $this->mapping->add($this->serviceFinder->find($directory));
        }

        $this->unloadedSourceDirectories = [];
    }

    public function setCache(?Cache $cache, bool $bindAsWell = true): self
    {
        if ($bindAsWell) {
            $this->bindService($cache, Cache::class);
        }

        $this->serviceFinder->setCache($cache);

        return $this;
    }

    public function bindService(object $service, string ...$forTypes): void
    {
        $this->services[$service::class] = $service;

        foreach ($forTypes as $forType) {
            $this->mapping->set($forType, $service::class);
        }
    }

    /**
     * The return value  is an object of type $type. This is specified in PhpStorm in .phpstorm.meta.php
     */
    public function resolve(string $type): object
    {
        if (null === $service = $this->findService($type)) {
            throw new Exceptions\ServiceNotFoundByTypeException($type);
        }

        if (!array_key_exists($service, $this->services)) {
            $this->services[$service] = $this->instantiator->instantiate($service);
        }

        return $this->services[$service];
    }

    public function findService(string $type): ?string
    {
        if ($this->mapping->has($type)) {
            return $this->mapping->get($type);
        }

        $this->analyseSources();

        return $this->mapping->has($type) ? $this->mapping->get($type) : null;
    }

    public function instantiate(string $className): object
    {
        return $this->instantiator->instantiate($className);
    }

    public function getServiceClassNames(): array
    {
        return $this->mapping->getAll();
    }
}
