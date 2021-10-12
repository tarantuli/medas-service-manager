<?php

declare(strict_types=1);

namespace Medas\ServiceContainer;

class ServiceInstantiator
{
    public function __construct(private ServiceContainer $container)
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
        PreferredClassMap    $preferredClassMap): object
    {
        $paramService = null;
        $types = $this->getParameterTypes($parameter);
        $checkedTypes = [];

        foreach ($types as $type) {
            $type = $type->getName();

            if ($preferredClass = $preferredClassMap->forType($type)) {
                $checkedTypes[] = $preferredClass;

                if (null !== $this->container->findService($preferredClass)) {
                    $paramService = $preferredClass;
                    break;
                }
            }

            $checkedTypes[] = $type;
            if (null !== $this->container->findService($type)) {
                $paramService = $type;
                break;
            }
        }

        if (null === $paramService) {
            throw new Exceptions\ServiceNotFoundByTypesException($checkedTypes);
        }

        return $this->container->resolve($paramService);
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
