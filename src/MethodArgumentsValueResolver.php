<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\ServiceManager\Attributes\EnvValue;
use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\Interfaces\EnvManager;

#[Service]
class MethodArgumentsValueResolver
{
    private PreferredClassMap $preferredClassMap;

    private mixed $foundEnvValue;
    private object $foundClassValue;

    public function __construct(private ServiceManager $serviceManager)
    {
        $this->serviceManager->bindService($this, MethodArgumentsValueResolver::class);
    }

    public function resolve(\ReflectionMethod $method): array
    {
        $this->preferredClassMap = new PreferredClassMap($method);
        $arguments = [];

        foreach ($method->getParameters() as $parameter) {
            $arguments[] = $this->getMethodArgumentValue($parameter);
        }

        return $arguments;
    }

    private function getMethodArgumentValue(\ReflectionParameter $parameter): mixed
    {
        if ($this->findEnvValue($parameter)) {
            return $this->foundEnvValue;
        }

        if ($this->findClassValue($parameter)) {
            return $this->foundClassValue;
        }

        throw new Exceptions\CouldNotResolveMethodArgumentException($parameter);
    }

    private function findEnvValue(\ReflectionParameter $parameter): bool
    {
        if (!$attributes = $parameter->getAttributes(EnvValue::class)) {
            return false;
        }

        $path = $attributes[0]->newInstance()->path;
        $this->foundEnvValue = $this->serviceManager->resolve(EnvManager::class)->getValue($path);

        return true;
    }

    private function findClassValue(\ReflectionParameter $parameter): bool
    {
        $service = null;
        $types = $this->getParameterTypes($parameter);

        foreach ($types as $type) {
            $typeName = $type->getName();

            if ($preferredClass = $this->preferredClassMap->forType($typeName)) {
                if (null !== $this->serviceManager->findService($preferredClass)) {
                    $service = $preferredClass;
                    break;
                }
            }

            if (null !== $this->serviceManager->findService($typeName)) {
                $service = $typeName;
                break;
            }
        }

        if (null === $service) {
            return false;
        }

        $this->foundClassValue = $this->serviceManager->resolve($service);

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
