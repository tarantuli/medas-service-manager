<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Registry;

use Medas\Core\Interfaces\{Package, ServiceConfig};
use Medas\ServiceManager\Mapping\MappingManager;

/**
 * Owns the lifecycle of all registered packages:
 *   - Guards against duplicate registrations
 *   - Recursively registers dependencies before the dependant package
 *   - Keeps the list sorted by descending priority
 *   - Notifies the MappingManager so service discovery covers every package directory
 *   - Optionally initializes a package immediately (used when adding packages after bootstrap)
 */
class PackageRegistry
{
    /** @var Package[] keyed by class name for O(1) duplicate checks */
    private array $packages = [];

    public function __construct(
        private readonly MappingManager $mappingManager,
    )
    {
    }

    /**
     * Registers a package and all of its transitive dependencies.
     *
     * @param ServiceConfig $config Passed through to {@see Package::initialize()} when $doInitialize is true.
     */
    public function add(Package $package, ServiceConfig $config, bool $doInitialize = false): self
    {
        if (array_key_exists($package::class, $this->packages)) {
            return $this;
        }

        // Register dependencies first so they are always available when the dependant package initializes.
        foreach ($package->dependencies() as $dependency) {
            $this->add($dependency, $config);
        }

        $this->packages[$package::class] = $package;

        uasort(
            $this->packages,
            fn(Package $a, Package $b) => -$a->priority() <=> $b->priority(),
        );

        $this->mappingManager->addPackage($package);

        if ($doInitialize) {
            $package->initialize($config);
        }

        return $this;
    }

    /**
     * Convenience wrapper for registering multiple packages at once.
     *
     * @param Package[] $packages
     */
    public function addMultiple(array $packages, ServiceConfig $config): self
    {
        foreach ($packages as $package) {
            $this->add($package, $config);
        }

        return $this;
    }

    /** @return Package[] */
    public function all(): array
    {
        return $this->packages;
    }
}
