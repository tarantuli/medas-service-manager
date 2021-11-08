<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Interfaces;

interface ConfigManager
{
    public function getValue(string $path): mixed;

    public function hasValue(string $path): bool;
}
