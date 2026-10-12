<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Registry;

use Medas\Core\Interfaces\Package;
use Medas\ServiceManager\{Mapping\MappingManager, ServiceConfigBuilder};

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
     * @param ServiceConfigBuilder $builder Passed through to {@see Package::initialize()} when $doInitialize is true.
     */
    public function add(Package $package, ServiceConfigBuilder $builder, bool $doInitialize = false): self
    {
        if (array_key_exists($package::class, $this->packages)) {
            return $this;
        }

        // Register dependencies first so they are always available when the dependant package initializes.
        foreach ($package->dependencies() as $dependency) {
            $this->add($dependency, $builder);
        }

        if ($builder->isDev) {
            foreach ($package->devDependencies() as $dependency) {
                $this->add($dependency, $builder);
            }
        }

        $this->packages[$package::class] = $package;

        uasort(
            $this->packages,
            fn(Package $a, Package $b) => -$a->priority() <=> $b->priority(),
        );

        $this->mappingManager->addPackage($package);

        if ($doInitialize) {
            $package->initialize($builder);
        }

        return $this;
    }

    /**
     * Convenience wrapper for registering multiple packages at once.
     *
     * @param Package[] $packages
     */
    public function addMultiple(array $packages, ServiceConfigBuilder $config): self
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
