<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

use Medas\Core\Interfaces\Package;
use Medas\ServiceManager\{Cache\CacheManager, ServiceManager};

/**
 * Discovers the services of each package as it's added, and collects them, with the type bindings, in one
 * `ServiceMapping`.
 *
 * `ServiceManager` and `CacheManager` are bound to themselves from the start, because `ServiceManager` creates both
 * itself.
 *
 * @see ServiceFinder
 */
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
            CacheManager::class,
        ];

        foreach ($defaultMappings as $defaultMapping) {
            $this->mapping->set($defaultMapping, $defaultMapping);
        }
    }

    /**
     * @sideEffect Loads every class file in the package's source directory.
     */
    public function addPackage(Package $package): void
    {
        $this->mapping->addFromPackage($this->serviceFinder->find(
            $package->sourceDirectory(),
            $package->isTestPackage()
        ));
    }

    public function get(): ServiceMapping
    {
        return $this->mapping;
    }
}
