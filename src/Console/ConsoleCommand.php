<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Console;

interface ConsoleCommand
{
    public function group(): ConsoleCommandGroup;

    public function name(): string;

    public function description(): string;

    public function fullCommand(): string;

    public function process(array $arguments);
}
