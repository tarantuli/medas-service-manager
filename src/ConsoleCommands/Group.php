<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ConsoleCommands;

use Medas\Console\Commands\{BaseConsoleCommandGroup, ConsoleCommandGroup};
use Medas\Core\Attributes\Service;

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
