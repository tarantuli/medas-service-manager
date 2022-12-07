<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ParameterResolving;

interface ArgumentProcessor
{
    public function process(\ReflectionParameter $parameter, mixed $argument): mixed;

    public function priority(): int;
}
