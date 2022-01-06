<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Exceptions;

use Medas\Core\Exceptions\BaseException;

class CircularDependencyException extends BaseException
{
    public function __construct(array $trace, string $current)
    {
        parent::__construct(implode(' > ', array_merge($trace, [$current])));
    }

    public function pattern(): string
    {
        return 'circular dependency found: %s';
    }
}
