<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\ServiceManager\Attributes\EnvValue;
use Medas\ServiceManager\Interfaces\EnvManager;

class ServiceInstantiator
{
    private mixed $foundEnvValue;

    public function __construct(private ServiceManager $manager)
    {
    }

    public function instantiate(string $className): object
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

    private function getMethodArgumentValue(
        \ReflectionParameter $parameter,
        PreferredClassMap    $preferredClassMap): mixed
    {
        if ($this->foundEnvValue($parameter)) {
            return $this->foundEnvValue;
        }

        $paramService = null;
        $types = $this->getParameterTypes($parameter);
        $checkedTypes = [];

        foreach ($types as $type) {
            $type = $type->getName();

            if ($preferredClass = $preferredClassMap->forType($type)) {
                $checkedTypes[] = $preferredClass;

                if (null !== $this->manager->findService($preferredClass)) {
                    $paramService = $preferredClass;
                    break;
                }
            }

            $checkedTypes[] = $type;
            if (null !== $this->manager->findService($type)) {
                $paramService = $type;
                break;
            }
        }

        if (null === $paramService) {
            throw new Exceptions\ServiceNotFoundByTypesException($checkedTypes);
        }

        return $this->manager->resolve($paramService);
    }

    private function foundEnvValue(\ReflectionParameter $parameter): bool
    {
        if (!$attributes = $parameter->getAttributes(EnvValue::class)) {
            return false;
        }

        $path = $attributes[0]->newInstance()->path;
        $this->foundEnvValue = $this->manager->resolve(EnvManager::class)->getValue($path);

        return true;
    }

    /** @return \ReflectionNamedType[] */
    private function getParameterTypes(\ReflectionParameter $parameter): array
    {
        $type = $parameter->getType();

        if (!$type) return [];

        return $type instanceof \ReflectionUnionType
            ? $type->getTypes()
            : [$type];
    }
}
