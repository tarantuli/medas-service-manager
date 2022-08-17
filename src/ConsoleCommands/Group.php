<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ConsoleCommands;

use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\Console\{BaseConsoleCommandGroup, ConsoleCommandGroup};

#[Service]
class Group extends BaseConsoleCommandGroup
{
    public function parent(): ConsoleCommandGroup|null
    {
        return null;
    }

    public function name(): string
    {
        return 'services';
    }
}
