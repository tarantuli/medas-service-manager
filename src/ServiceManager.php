<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\Directory;
use Medas\ServiceManager\Attributes\Service;

class ServiceManager
{
    private static self $instance;

    public static function get(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /** @var object[] */
    private array $services = [];

    /** @var string[] */
    private array $mapping = [];

    /** @var string[] */
    private array $sourceDirectories = [];

    /** @var string[] */
    private array $unloadedSourceDirectories = [];

    private ServiceInstantiator $instantiator;
    private int $mostRecentFileModtime;

    private function __construct()
    {
        $this->instantiator = new ServiceInstantiator($this);
        $this->declareGlobalFunctions();
    }

    private function declareGlobalFunctions(): void
    {
        require_once 'GlobalFunctions.php';
    }

    public function addPackages(array $classes): void
    {
        foreach ($classes as $class) {
            $this->addPackage(byClass: $class, scanImmediately: false);
        }

        $this->loadSources();
    }

    public function addPackage(string $byClass = null, string $byDirectory = null, bool $scanImmediately = true): void
    {
        if (null !== $byClass) {
            $class = new \ReflectionClass($byClass);
            $this->addSourceDirectory(dirname($class->getFileName()));
        }

        if (null !== $byDirectory) {
            $this->addSourceDirectory($byDirectory);
        }

        if ($scanImmediately) {
            $this->loadSources();
        }
    }

    private function addSourceDirectory(string $sourceDirectory): void
    {
        $this->sourceDirectories[] = $sourceDirectory;
        $this->unloadedSourceDirectories[] = $sourceDirectory;
    }

    private function loadSources(): void
    {
        $loadedFile = false;

        foreach ($this->unloadedSourceDirectories as $sourceDirectory) {
            $files = Directory::recursiveFindByExtension($sourceDirectory, 'php');

            foreach ($files as $file) {
                require_once $file;
                $loadedFile = true;
                $modtime = filemtime($file);

                if (!isset($this->mostRecentFileModtime) || $this->mostRecentFileModtime < $modtime) {
                    $this->mostRecentFileModtime = $modtime;
                }
            }
        }

        if ($loadedFile) {
            $this->findServices();
        }

        $this->unloadedSourceDirectories = [];
    }

    private function findServices(): void
    {
        foreach (get_declared_classes() as $className) {
            $class = new \ReflectionClass($className);

            if (!$class->isInternal() && $class->getAttributes(Service::class)) {
                $this->registerClass($class);
            }
        }
    }

    private function registerClass(\ReflectionClass $class, array $forTypes = []): void
    {
        if (!$forTypes) {
            $forTypes = $this->getSelfParentsAndInterfaces($class);
        }

        foreach ($forTypes as $forType) {
            $this->mapping[$forType] = $class->name;
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
        if (array_key_exists($type, $this->mapping)) {
            return $this->mapping[$type];
        }

        $this->loadSources();

        return $this->mapping[$type] ?? null;
    }

    public function bindService(object $service, string ...$forTypes): void
    {
        $this->services[$service::class] = $service;
        $this->registerClass(new \ReflectionClass($service), $forTypes);
    }

    public function getMostRecentFileModtime(): int
    {
        return $this->mostRecentFileModtime;
    }
}
