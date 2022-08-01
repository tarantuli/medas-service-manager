<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\ParameterResolver\ParameterResolver;
use Medas\ServiceManager\ParameterResolver\ServiceByType;

#[Service]
class MethodArgumentsValueResolver
{
    /** @var ParameterResolver[] */
    private array $resolvers = [];

    public function __construct(private ServiceManager $serviceManager)
    {
        // This service is *not* instantiated automatically, so don't add more dependencies,
        // expecting them to be injected.
        $this->resolvers[] = new ServiceByType($this->serviceManager);
    }

    public function addResolver(ParameterResolver $resolver): void
    {
        $this->resolvers[] = $resolver;
        usort($this->resolvers,
            fn(ParameterResolver $a, ParameterResolver $b) => -($a->priority() <=> $b->priority()));
    }

    public function resolve(\ReflectionMethod $method): array
    {
        $arguments = [];

        foreach ($method->getParameters() as $parameter) {
            $arguments[] = $this->getMethodArgumentValue($method, $parameter);
        }

        return $arguments;
    }

    private function getMethodArgumentValue(\ReflectionMethod $method, \ReflectionParameter $parameter): mixed
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->handle($method, $parameter)) {
                return $resolver->result();
            }
        }
        if ($parameter->allowsNull()) {
            return null;
        }

        throw new Exceptions\CouldNotResolveMethodArgumentException($parameter);
    }
}
