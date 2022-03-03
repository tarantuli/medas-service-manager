<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\Interfaces\Cache;

#[Service]
class ServiceFinder
{
    private FileFinder $fileFinder;
    private Cache|null $cache = null;

    public function __construct()
    {
        $this->fileFinder = new FileFinder();
    }

    public function find(string $directory): array
    {
        return $this->cache
            ? $this->cache->get([$this::class, $directory], fn() => $this->findServices($directory))
            : $this->findServices($directory);
    }

    private function findServices(string $directory): array
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

            if (!$class->isInternal() && $class->getAttributes(Service::class)
                && str_starts_with($class->getFileName(), $directory)) {
                $this->registerClass($class, $services);
            }
        }

        return $services;
    }

    private function registerClass(\ReflectionClass $class, array &$services): void
    {
        $forTypes = $this->getSelfParentsAndInterfaces($class);

        foreach ($forTypes as $forType) {
            $services[$forType] = $class->name;
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

    public function setCache(?Cache $cache): self
    {
        $this->cache = $cache;

        return $this;
    }
}
