<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

use Medas\Core\{Attributes\Service, Interfaces\ParameterResolver, ParameterResolverResult};
use Medas\ServiceManager\ServiceManager;

#[Service]
readonly class ManualBindingFinder implements ParameterResolver
{
    public function priority(): int
    {
        return -50;
    }

    public function handle(\ReflectionParameter|\ReflectionProperty $parameter): ParameterResolverResult
    {
        if ($parameter instanceof \ReflectionProperty) {
            return new ParameterResolverResult(false);
        }

        $bindings = \service(ServiceManager::class)->config()->parameterBindings();

        if ($bindings->storage->contains($parameter)) {
            return new ParameterResolverResult(true, $bindings->storage[$parameter]);
        }

        return new ParameterResolverResult(false);
    }
}
