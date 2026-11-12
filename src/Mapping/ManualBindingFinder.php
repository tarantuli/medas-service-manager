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

/**
 * Supplies constructor and method arguments that were bound by hand with `ServiceConfigBuilder::addManualBinding()`,
 * for parameters the object instantiator can't resolve on its own, like scalars.
 *
 * Bindings are looked up by class, method and parameter name.
 *
 * @assumes The `ServiceManager` is initialized; the bindings come from its configuration through `sm()`.
 * @assumes Bindings are matched against the class that declares the method. For a subclass that inherits its
 *          constructor, the binding must be registered for the parent class, and then applies to every subclass
 *          that inherits it.
 * @cache Bindings are part of the cached `ServiceConfig`, so a changed binding only takes effect after the caches
 *        are cleared.
 * @notFor Properties, closures and global functions, which can't have a binding.
 * @see \Medas\ServiceManager\ServiceConfigBuilder
 */
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
