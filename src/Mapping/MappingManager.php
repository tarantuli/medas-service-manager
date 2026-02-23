<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

use Medas\Core\Interfaces\Package;
use Medas\ObjectInstantiator\{ObjectInstantiator, ParameterResolving\ParameterResolveManager};
use Medas\ServiceManager\{Cache\CacheManager, ServiceManager};

class MappingManager
{
    private ServiceFinder $serviceFinder;
    private ServiceMapping $mapping;

    public function __construct()
    {
        // This should not be a service, it is *not* instantiated automatically,
        // so don't add more dependencies, expecting them to be injected.
        $this->mapping = new ServiceMapping();
        $this->serviceFinder = new ServiceFinder();

        $this->addDefaultMappings();
    }

    private function addDefaultMappings(): void
    {
        $defaultMappings = [
            ServiceManager::class,
            ObjectInstantiator::class,
            ParameterResolveManager::class,
            CacheManager::class,
        ];

        foreach ($defaultMappings as $defaultMapping) {
            $this->mapping->set($defaultMapping, $defaultMapping);
        }
    }

    public function addPackage(Package $package): void
    {
        $this->mapping->addFromPackage($this->serviceFinder->find($package->sourceDirectory()));
    }

    public function get(): ServiceMapping
    {
        return $this->mapping;
    }
}
