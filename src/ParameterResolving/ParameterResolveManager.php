<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ParameterResolving;

use Medas\ServiceManager\{Attributes\Service, Exceptions, ServiceManager};

#[Service]
class ParameterResolveManager
{
    /** @var ParameterResolver[] */
    private array $resolvers = [];

    /** @var ArgumentProcessor[] */
    private array $processors = [];

    public function __construct(
        private readonly ServiceManager $serviceManager,
    )
    {
        // This service is *not* instantiated automatically,
        // so don't add more dependencies, expecting them to be injected.
        $this->resolvers[] = new ServiceFinderByType($this->serviceManager);

        $this->serviceManager->bindService($this, ParameterResolveManager::class);
    }

    public function addResolver(ParameterResolver $resolver): void
    {
        $this->resolvers[] = $resolver;
        usort($this->resolvers,
            fn(ParameterResolver $a, ParameterResolver $b) => -($a->priority() <=> $b->priority()));
    }

    public function addProcessor(ArgumentProcessor $processor): void
    {
        $this->processors[] = $processor;
        usort($this->processors,
            fn(ArgumentProcessor $a, ArgumentProcessor $b) => -($a->priority() <=> $b->priority()));
    }

    public function resolveMethod(\ReflectionMethod $method, array $givenArguments): array
    {
        $arguments = [];

        foreach ($method->getParameters() as $parameter) {
            if (array_key_exists($parameter->name, $givenArguments)) {
                $argument = $givenArguments[$parameter->name];
            }
            else {
                $argument = $this->resolveParameter($parameter);
            }

            $arguments[] = $this->processArgument($parameter, $argument);
        }

        return $arguments;
    }

    public function resolveParameter(\ReflectionParameter|\ReflectionProperty $parameter): mixed
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->handle($parameter)) {
                return $resolver->result();
            }
        }

        if ($parameter instanceof \ReflectionProperty && $parameter->hasDefaultValue()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter instanceof \ReflectionParameter && $parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new Exceptions\CouldNotResolveParameter($parameter);
    }

    private function processArgument(\ReflectionParameter $parameter, mixed $argument): mixed
    {
        foreach ($this->processors as $processor) {
            $argument = $processor->process($parameter, $argument);
        }

        return $argument;
    }
}
