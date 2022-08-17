<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Console;

interface ConsoleCommandGroup
{
    public function parent(): ConsoleCommandGroup|null;

    public function name(): string;

    public function path(): string;
}
