<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ParameterResolver;

interface ParameterResolver
{
    public function handle(\ReflectionMethod $method, \ReflectionParameter $parameter): bool;

    public function result(): mixed;

    public function priority(): int;
}
