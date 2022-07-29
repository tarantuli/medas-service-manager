<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ConfigOptions;

use Medas\ServiceManager\Interfaces\IsSingleton;

interface ConfigOption extends IsSingleton
{
    public function group(): ConfigGroup;

    public function name(): string;

    public function description(): string;

    public function hasDefault(): bool;

    public function default(): mixed;
}
