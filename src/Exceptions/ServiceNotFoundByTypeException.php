<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Exceptions;

use Medas\Core\Exceptions\BaseException;

class ServiceNotFoundByTypeException extends BaseException
{
    public function __construct(string $type)
    {
        parent::__construct($type);
    }

    public function getPattern(): string
    {
        return 'service not found with type %s';
    }
}
