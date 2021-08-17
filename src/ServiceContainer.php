<?php

namespace Medas\ServiceContainer;

use Medas\ServiceContainer\Attributes\Service;

class ServiceContainer
{
    private static self $instance;

    public static function get(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @var array{object}
     */
    private array $services = [];

    /**
     * @var array{string}
     */
    private array $mapping = [];

    /**
     * @var array{string}
     */
    private array $sourceDirectories = [];
    private bool $sourcesHaveBeenLoaded = false;

    public function resolve(string $type): object
    {
        if (!array_key_exists($type, $this->mapping) && !$this->findService($type)) {
            throw new Exceptions\ServiceNotFoundByTypeException($type);
        }

        $service = $this->mapping[$type];

        if (!array_key_exists($service, $this->services)) {
            $this->services[$service] = $this->instantiate($service);
        }

        return $this->services[$service];
    }

    private function findService(string $type): bool
    {
        $this->loadSources();

        foreach (get_declared_classes() as $className) {
            $class = new \ReflectionClass($className);

            if ($class->getAttributes(Service::class)) {
                $this->registerService($class);

                if (array_key_exists($type, $this->mapping)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function loadSources(): void
    {
        if ($this->sourcesHaveBeenLoaded) {
            return;
        }
        foreach ($this->sourceDirectories as $sourceDirectory) {
            $iterator = new \RegexIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceDirectory)), '/^.+\.php$/i', \RegexIterator::GET_MATCH);

            foreach ($iterator as $filePath => $file) {
                require_once $filePath;
            }
        }

        $this->sourcesHaveBeenLoaded = true;
    }

    private function registerService(\ReflectionClass $class)
    {
        $serviceClass = $class->name;

        do {
            $this->mapping[$class->name] = $serviceClass;

            foreach ($class->getInterfaces() as $interface) {
                $this->mapping[$interface->name] = $serviceClass;
            }
        } while ($class = $class->getParentClass());

    }

    private function instantiate(string $service): object
    {
        $reflectionClass = new \ReflectionClass($service);

        $arguments = [];

        if ($constructor = $reflectionClass->getConstructor()) {
            foreach ($constructor->getParameters() as $parameter) {
                $arguments[] = $this->resolve($parameter);
            }
        }

        return new $service(... $arguments);
    }

    public function addSourceDirectory(string $sourceDirectory): void
    {
        $this->sourceDirectories[] = $sourceDirectory;
        $this->sourcesHaveBeenLoaded = false;
    }
}
