<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ParameterResolving;

use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\Mapping\PreferredClassMap;
use Medas\ServiceManager\ServiceManager;

#[Service]
class ServiceFinderByType implements ParameterResolver
{
    private object $result;

    public function __construct()
    {
        // This service is *not* instantiated automatically,
        // so don't add more dependencies, expecting them to be injected.
    }

    public function __serialize(): array
    {
        // This is needed, so $result isn't serialized
        return [];
    }

    public function __unserialize(array $data): void
    {
        // Do nothing
    }

    public function priority(): int
    {
        return -200;
    }

    public function handle(\ReflectionParameter|\ReflectionProperty $parameter): bool
    {
        $serviceManager = ServiceManager::get();
        $preferredClassMap = $parameter instanceof \ReflectionParameter
            ? new PreferredClassMap($parameter->getDeclaringFunction())
            : [];

        $service = null;
        $types = parameterTypes($parameter);

        foreach ($types as $type) {
            $typeName = $type->getName();

            if ($preferredClass = $preferredClassMap->forType($typeName)) {
                if (null !== $serviceManager->findService($preferredClass)) {
                    $service = $preferredClass;
                    break;
                }
            }

            if (null !== $serviceManager->findService($typeName)) {
                $service = $typeName;
                break;
            }
        }

        if (null === $service) {
            return false;
        }

        $this->result = $serviceManager->resolve($service);

        return true;
    }

    public function result(): object
    {
        return $this->result;
    }
}
