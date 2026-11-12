<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ConsoleCommands;

use Medas\Console\Commands\{BaseConsoleCommandGroup, ConsoleCommandGroup};
use Medas\Core\Attributes\Service;

/**
 * The top-level console command group `services`, for commands that inspect or maintain the service manager and its
 * caches.
 */
#[Service]
readonly class Group extends BaseConsoleCommandGroup
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
