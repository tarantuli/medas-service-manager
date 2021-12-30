<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Exceptions;

use Medas\Core\Exceptions\BaseException;

class CouldNotResolveMethodArgumentException extends BaseException
{
    public function __construct(\ReflectionParameter $parameter)
    {
        parent::__construct(
            $parameter->getDeclaringClass()->name,
            $parameter->getDeclaringFunction()->name,
            $parameter->name
        );
    }

    public function pattern(): string
    {
        return 'could not resolve value of %s::%s parameter $%s';
    }
}
