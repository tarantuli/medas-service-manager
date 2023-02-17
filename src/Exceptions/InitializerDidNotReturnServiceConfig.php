<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Exceptions;

use Medas\Core\Exceptions\BaseException;

class InitializerDidNotReturnServiceConfig extends BaseException
{
    public function __construct(mixed $returnValue)
    {
        parent::__construct(get_debug_type($returnValue));
    }

    public function pattern(): string
    {
        return 'the initializer should return a ServiceConfig object, but returned a(n) %s';
    }
}
