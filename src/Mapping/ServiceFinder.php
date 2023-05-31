<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

use Medas\Core\Attributes\Service;

class ServiceFinder
{
    private FileFinder $fileFinder;

    public function __construct()
    {
        // This should not be a service, it is *not* instantiated automatically,
        // so don't add more dependencies, expecting them to be injected.

        $this->fileFinder = new FileFinder();
    }

    public function find(string $directory): array
    {
        return $this->loadFiles($directory) ? $this->analyseClasses($directory) : [];
    }

    private function loadFiles(string $directory): bool
    {
        $files = $this->fileFinder->recursiveFindByExtension($directory, 'php');
        $loadedFile = false;

        foreach ($files as $file) {
            require_once $file;
            $loadedFile = true;
        }

        return $loadedFile;
    }

    private function analyseClasses(string $directory): array
    {
        $services = [];

        foreach (get_declared_classes() as $className) {
            $class = new \ReflectionClass($className);

            if (
                !$class->isInternal()
                && str_starts_with($class->getFileName(), $directory)
                && $class->getAttributes(Service::class, \ReflectionAttribute::IS_INSTANCEOF)
            ) {
                $this->registerClass($class, $services);
            }
        }

        return $services;
    }

    private function registerClass(\ReflectionClass $class, array &$services): void
    {
        $forTypes = $this->getSelfParentsAndInterfaces($class);

        foreach ($forTypes as $forType) {
            $services[] = [$forType, $class->name];
        }
    }

    private function getSelfParentsAndInterfaces(\ReflectionClass $class): array
    {
        $classes = [];

        do {
            $classes[] = $class->name;

            foreach ($class->getInterfaces() as $interface) {
                $classes[] = $interface->name;
            }
        } while ($class = $class->getParentClass());

        return $classes;
    }
}
