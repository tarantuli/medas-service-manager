<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Console;

abstract class BaseConsoleCommand implements ConsoleCommand
{
    public function fullCommand(): string
    {
        return $this->group()->path() . ':' . $this->name();
    }
}
