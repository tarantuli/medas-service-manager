<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

use Medas\Core\{
    Attributes\Service,
    CompiledValue,
    HandlesByCompiling,
    Interfaces\CompilableParameterResolver,
    ParameterResolverResult
};

#[Service]
readonly class ManualBindingFinder implements CompilableParameterResolver
{
    use HandlesByCompiling;

    public function priority(): int
    {
        return -50;
    }

    public function compile(\ReflectionParameter|\ReflectionProperty $parameter): CompiledValue|null
    {
        if ($parameter instanceof \ReflectionProperty) {
            return null;
        }

        $bindings = sm()->config->manualBindings;

        // Parameters of closures and functions have no declaring class, so they can't have a binding
        $className = $parameter->getDeclaringClass()?->name;
        $methodName = $parameter->getDeclaringFunction()->name;

        if ($className === null || !isset($bindings[$className][$methodName][$parameter->name])) {
            return null;
        }

        return new CompiledValue($bindings[$className][$methodName][$parameter->name]);
    }

    /**
     * @param CompiledValue $compiled
     */
    public function resolveCompiled(object $compiled): ParameterResolverResult
    {
        return new ParameterResolverResult(true, $compiled->value);
    }
}
