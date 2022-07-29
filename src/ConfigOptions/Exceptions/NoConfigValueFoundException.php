<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ConfigOptions\Exceptions;

use Medas\Core\Exceptions\BaseException;
use Medas\ServiceManager\ConfigOptions\ConfigOption;

class NoConfigValueFoundException extends BaseException
{
    public function __construct(ConfigOption $configOption)
    {
        parent::__construct($configOption::class);
    }

    public function pattern(): string
    {
        return 'No config value found for %s';
    }
}
