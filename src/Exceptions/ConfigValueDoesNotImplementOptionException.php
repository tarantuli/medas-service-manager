<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Exceptions;

use Medas\Core\Exceptions\BaseException;

class ConfigValueDoesNotImplementOptionException extends BaseException
{
    public function __construct(string $configValue)
    {
        parent::__construct($configValue);
    }

    public function pattern(): string
    {
        return 'config value %s is not a class implementing ConfigOption';
    }
}
