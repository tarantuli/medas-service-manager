<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ParameterResolving;

interface ParameterResolver
{
    public function priority(): int;

    public function handle(\ReflectionParameter|\ReflectionProperty $parameter): bool;

    public function result(): mixed;
}
