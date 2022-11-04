<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ParameterResolving;

use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\Exceptions;
use Medas\ServiceManager\ServiceManager;

#[Service]
class ParameterResolveManager
{
    /** @var ParameterResolver[] */
    private array $resolvers = [];

    public function __construct(
        private readonly ServiceManager $serviceManager,
    )
    {
        // This service is *not* instantiated automatically, so don't add more dependencies,
        // expecting them to be injected.
        $this->resolvers[] = new ServiceFinderByType($this->serviceManager);
    }

    public function addResolver(ParameterResolver $resolver): void
    {
        $this->resolvers[] = $resolver;
        usort($this->resolvers,
            fn(ParameterResolver $a, ParameterResolver $b) => -($a->priority() <=> $b->priority()));
    }

    public function resolveMethod(\ReflectionMethod $method, array $givenArguments): array
    {
        $arguments = [];

        foreach ($method->getParameters() as $parameter) {
            if (array_key_exists($parameter->name, $givenArguments)) {
                $arguments[] = $givenArguments[$parameter->name];
            }
            else {
                $arguments[] = $this->resolveParameter($method, $parameter);
            }
        }

        return $arguments;
    }

    public function resolveParameter(\ReflectionMethod $method, \ReflectionParameter $parameter): mixed
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->handle($method, $parameter)) {
                return $resolver->result();
            }
        }
        if ($parameter->allowsNull()) {
            return null;
        }

        throw new Exceptions\CouldNotResolveParameterException($parameter);
    }
}
