<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Registry;

use Medas\Core\Interfaces\Package;
use Medas\ServiceManager\{Mapping\MappingManager, ServiceConfigBuilder};

/**
 * Keeps track of the registered packages:
 *
 * - It registers each package once.
 * - It registers the dependencies of a package before the package itself, and dev dependencies too in dev mode.
 * - It keeps the list sorted by descending priority, which is the order in which `initialize()` and `ready()` are
 *   called later. A dependency only comes before the packages that need it when its priority is at least as high, so
 *   a dependency with a lower priority is initialised after them.
 * - It passes every package to the `MappingManager`, so its services are discovered.
 * - It can initialise a package right away, for packages added after bootstrap.
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
     * @param ServiceConfigBuilder $builder Passed to `Package::initialize()` when `$doInitialize` is `true`.
     */
    public function add(Package $package, ServiceConfigBuilder $builder, bool $doInitialize = false): self
    {
        if (array_key_exists($package::class, $this->packages)) {
            return $this;
        }

        // Register dependencies first, so their services are discovered before this package's. The list is sorted by
        // priority below, so this only decides the order among packages with equal priority. Dependencies aren't
        // initialised here, even when $doInitialize is set
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
            fn(Package $a, Package $b) => $b->priority() <=> $a->priority(),
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
