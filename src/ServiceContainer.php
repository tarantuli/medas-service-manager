<?php

declare(strict_types=1);

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
     * @var object[]
     */
    private array $services = [];

    /**
     * @var string[]
     */
    private array $mapping = [];

    /**
     * @var string[]
     */
    private array $sourceDirectories = [];
    /**
     * @var string[]
     */
    private array $unloadedSourceDirectories = [];

    private ServiceInstantiator $instantiator;

    public function __construct()
    {
        $this->instantiator = new ServiceInstantiator($this);
    }

    public function addSourceDirectory(string $sourceDirectory): void
    {
        $this->sourceDirectories[] = $sourceDirectory;
        $this->unloadedSourceDirectories[] = $sourceDirectory;
    }

    public function resolve(string $type): object
    {
        if (null === $service = $this->findService($type)) {
            throw new Exceptions\ServiceNotFoundByTypesException([$type]);
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
        $this->findServices();

        return $this->mapping[$type] ?? null;
    }

    private function loadSources(): void
    {
        foreach ($this->unloadedSourceDirectories as $sourceDirectory) {
            $iterator = new \RegexIterator(
                new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceDirectory)),
                '/^.+\.php$/i',
                \RegexIterator::GET_MATCH
            );

            foreach ($iterator as $filePath => $file) {
                require_once $filePath;
            }
        }

        $this->unloadedSourceDirectories = [];
    }

    private function findServices(): void
    {
        foreach (get_declared_classes() as $className) {
            $class = new \ReflectionClass($className);

            if (!$class->isInternal() && $class->getAttributes(Service::class)) {
                $this->registerService($class);
            }
        }
    }

    private function registerService(\ReflectionClass $class)
    {
        $serviceName = $class->name;

        do {
            $this->mapping[$class->name] = $serviceName;

            foreach ($class->getInterfaces() as $interface) {
                $this->mapping[$interface->name] = $serviceName;
            }
        } while ($class = $class->getParentClass());
    }
}
