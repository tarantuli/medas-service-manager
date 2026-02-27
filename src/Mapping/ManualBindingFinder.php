<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

use Medas\Core\{Attributes\Service, Interfaces\ParameterResolver, ParameterResolverResult};
use Medas\ServiceManager\ServiceConfig;

#[Service]
readonly class ManualBindingFinder implements ParameterResolver
{
    public function __construct(
        private ServiceConfig $config,
    )
    {
        // This service is *not* instantiated automatically,
        // so don't add more dependencies, expecting them to be injected.
    }

    public function priority(): int
    {
        return -50;
    }

    public function handle(\ReflectionParameter|\ReflectionProperty $parameter): ParameterResolverResult
    {
        if ($parameter instanceof \ReflectionProperty) {
            return new ParameterResolverResult(false);
        }

        $bindings = $this->config->manualBindings();
        $className = $parameter->getDeclaringClass()->name;
        $methodName = $parameter->getDeclaringFunction()->name;

        if (isset($bindings[$className][$methodName][$parameter->name])) {
            return new ParameterResolverResult(
                true,
                $bindings[$className][$methodName][$parameter->name],
            );
        }

        return new ParameterResolverResult(false);
    }
}
