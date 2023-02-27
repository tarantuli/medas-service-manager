<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Exceptions;

use Medas\Core\Exceptions\BaseException;

class MultipleImplementorsFound extends BaseException
{
    public function __construct(string $type, array $implementors)
    {
        parent::__construct($type, implode(', ', $implementors));
    }

    public function pattern(): string
    {
        return 'Found multiple services implementing %s: %s. Bind the one you want to use using sm()->bindService()';
    }
}
