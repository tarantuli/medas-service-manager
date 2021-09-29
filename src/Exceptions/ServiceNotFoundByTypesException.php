<?php

declare(strict_types=1);

namespace Medas\ServiceContainer\Exceptions;

class ServiceNotFoundByTypesException extends BaseException
{
    public function __construct(array $types)
    {
        parent::__construct($types);
    }

    public function getPattern(): string
    {
        return 'service not found with types %s';
    }
}
