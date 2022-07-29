<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ConfigOptions;

use Medas\ServiceManager\Interfaces\IsSingleton;

interface ConfigGroup extends IsSingleton
{
    public function parent(): ConfigGroup|null;

    public function name(): string;
}
