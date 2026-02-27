<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

use Medas\Core\Attributes\Service;

class ServiceFinder
{
    private Psr4FileLoader $psr4FileLoader;
    private UnsafeFileLoader $unsafeFileLoader;

    public function __construct()
    {
        $this->psr4FileLoader = new Psr4FileLoader();
        $this->unsafeFileLoader = new UnsafeFileLoader();
    }

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

        return array_unique($classes);
    }
}
