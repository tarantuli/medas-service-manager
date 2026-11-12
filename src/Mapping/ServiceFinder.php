<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

use Medas\Core\Attributes\Service;

/**
 * Finds the services in a package's source directory: every non-abstract class with `#[Service]`, or an attribute
 * that extends it, declared in a file in that directory.
 *
 * Each service is mapped to its own class, every interface it implements, and its parent classes, so it can be
 * resolved by any of them. A parent class that is a service itself is left out: it keeps resolving to itself,
 * instead of becoming ambiguous.
 *
 * @assumes A class belongs to the directory when its file path starts with the directory path.
 * @sideEffect Loads every class file in the directory first: through the Composer autoloader with
 *             `Psr4FileLoader`, or with `require_once` through `UnsafeFileLoader` for test packages.
 * @performance Checks every declared class for every package, so the work grows with the number of packages times
 *              the number of classes. It only runs while the config is built.
 */
class ServiceFinder
{
    private Psr4FileLoader $psr4FileLoader;
    private UnsafeFileLoader $unsafeFileLoader;

    public function __construct()
    {
        $this->psr4FileLoader = new Psr4FileLoader();
        $this->unsafeFileLoader = new UnsafeFileLoader();
    }

    /**
     * @param bool $allowUnguardedLoad Load the files with `require_once` instead of the autoloader.
     *
     * @return array{string, class-string}[] Pairs of a type and the service class it maps to. Empty when no file was
     *                                       loaded.
     */
    public function find(string $directory, bool $allowUnguardedLoad): array
    {
        $loadedAny = $allowUnguardedLoad
            ? $this->unsafeFileLoader->load($directory)
            : $this->psr4FileLoader->load($directory);

        return $loadedAny ? $this->analyseClasses($directory) : [];
    }

    private function analyseClasses(string $directory): array
    {
        $services = [];

        foreach (get_declared_classes() as $className) {
            $class = new \ReflectionClass($className);

            if (!$class->isInternal()
                    && str_starts_with($class->getFileName(), $directory)
                    && $class->getAttributes(Service::class, \ReflectionAttribute::IS_INSTANCEOF)) {
                $this->registerClass($class, $services);
            }
        }

        return $services;
    }

    private function registerClass(\ReflectionClass $class, array &$services): void
    {
        if ($class->isAbstract()) {
            return;
        }

        $forTypes = $this->getSelfParentsAndInterfaces($class);

        foreach ($forTypes as $forType) {
            $services[] = [$forType, $class->name];
        }
    }

    private function getSelfParentsAndInterfaces(\ReflectionClass $class): array
    {
        $classes = [$class->name];

        // Includes the interfaces of the parent classes
        foreach ($class->getInterfaces() as $interface) {
            $classes[] = $interface->name;
        }

        // A parent class that is a service itself keeps resolving to itself, instead of becoming ambiguous
        $parent = $class;

        while ($parent = $parent->getParentClass()) {
            if (!$this->isService($parent)) {
                $classes[] = $parent->name;
            }
        }

        return array_unique($classes);
    }

    private function isService(\ReflectionClass $class): bool
    {
        return !$class->isAbstract()
            && $class->getAttributes(Service::class, \ReflectionAttribute::IS_INSTANCEOF);
    }
}
