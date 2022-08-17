<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Console;

abstract class BaseConsoleCommandGroup implements ConsoleCommandGroup
{
    public function path(): string
    {
        return ($this->parent() ? $this->parent()->path() . ':' : '') . $this->name();
    }
}
