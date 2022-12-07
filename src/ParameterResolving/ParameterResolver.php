<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ParameterResolving;

interface ParameterResolver
{
    public function priority(): int;

    /** @todo factor out the $method parameter, it can be retrieved from $parameter if needed */
    public function handle(\ReflectionMethod $method, \ReflectionParameter $parameter): bool;

    public function result(): mixed;
}
