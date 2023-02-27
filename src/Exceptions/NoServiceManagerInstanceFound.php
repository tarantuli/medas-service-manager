<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Exceptions;

use Medas\Core\Exceptions\BaseException;

class NoServiceManagerInstanceFound extends BaseException
{
    public function pattern(): string
    {
        return 'No ServiceManager instance found';
    }
}
