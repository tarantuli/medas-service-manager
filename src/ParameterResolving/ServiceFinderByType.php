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

    public function __construct(
        private readonly ServiceManager $serviceManager,
    )
    {
    }

    public function priority(): int
    {
        return -200;
    }

    public function handle(\ReflectionParameter|\ReflectionProperty $parameter): bool
    {
        $preferredClassMap = $parameter instanceof \ReflectionParameter
            ? new PreferredClassMap($parameter->getDeclaringFunction())
            : [];

        $service = null;
        $types = parameterTypes($parameter);

        foreach ($types as $type) {
            $typeName = $type->getName();

            if ($preferredClass = $preferredClassMap->forType($typeName)) {
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

        $this->result = $this->serviceManager->resolve($service);

        return true;
    }

    public function result(): object
    {
        return $this->result;
    }
}
