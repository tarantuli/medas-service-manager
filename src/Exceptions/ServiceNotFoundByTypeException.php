<?php

namespace Medas\ServiceContainer\Exceptions;

class ServiceNotFoundByTypeException extends \Exception
{
    public function __construct($message, $code = 0, \Throwable $previous = null)
    {
        parent::__construct(sprintf('service not found with type %s', $message), $code, $previous);
    }
}
