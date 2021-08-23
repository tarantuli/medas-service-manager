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

    public function addSourceDirectory(string $sourceDirectory): void
    {
        $this->sourceDirectories[] = $sourceDirectory;
        $this->sourcesHaveBeenLoaded = false;
    }

    public function resolve(string $type): object
    {
        if (!$this->findService($type)) {
            throw new Exceptions\ServiceNotFoundByTypesException([$type]);
        }

        $service = $this->mapping[$type];

        if (!array_key_exists($service, $this->services)) {
            $this->services[$service] = $this->instantiate($service);
        }

        return $this->services[$service];
    }

    private function findService(string $type): bool
    {
        if (array_key_exists($type, $this->mapping)) {
            return true;
        }

        $this->loadSources();
        $this->findServices();

        return array_key_exists($type, $this->mapping);
    }

    private function loadSources(): void
    {
        if ($this->sourcesHaveBeenLoaded) {
            return;
        }

        foreach ($this->sourceDirectories as $sourceDirectory) {
            $iterator = new \RegexIterator(
                new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceDirectory)),
                '/^.+\.php$/i',
                \RegexIterator::GET_MATCH
            );

            foreach ($iterator as $filePath => $file) {
                require_once $filePath;
            }
        }

        $this->sourcesHaveBeenLoaded = true;
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

    private function instantiate(string $className): object
    {
        $class = new \ReflectionClass($className);

        $arguments = $this->getConstructorArgumentValues($class);

        return new $className(... $arguments);
    }

    private function getConstructorArgumentValues(\ReflectionClass $class): array
    {
        if (!$constructor = $class->getConstructor()) {
            return [];
        }

        return $this->getMethodArgumentValues($constructor);
    }

    private function getMethodArgumentValues(\ReflectionMethod $method): array
    {
        $arguments = [];
        $preferredClassMap = new PreferredClassMap($method);

        foreach ($method->getParameters() as $parameter) {
            $arguments[] = $this->getMethodArgumentValue($parameter, $preferredClassMap);
        }

        return $arguments;
    }

    private function getMethodArgumentValue(\ReflectionParameter $parameter, PreferredClassMap $preferredClassMap): object
    {
        $paramService = null;
        $types = $this->getParameterTypes($parameter);
        $checkedTypes = [];

        foreach ($types as $type) {
            $type = $type->getName();

            if ($preferredClass = $preferredClassMap->forType($type)) {
                $checkedTypes[] = $preferredClass;

                if ($this->findService($preferredClass)) {
                    $paramService = $preferredClass;
                    break;
                }
            }

            $checkedTypes[] = $type;
            if ($this->findService($type)) {
                $paramService = $type;
                break;
            }
        }

        if (null === $paramService) {
            throw new Exceptions\ServiceNotFoundByTypesException($checkedTypes);
        }

        return $this->resolve($paramService);
    }

    /**
     * @return \ReflectionNamedType[]
     */
    private function getParameterTypes(\ReflectionParameter $parameter): array
    {
        $type = $parameter->getType();

        if (!$type) return [];

        return $type instanceof \ReflectionUnionType
            ? $type->getTypes()
            : [$type];
    }
}
