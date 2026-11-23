<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ConsoleCommands;

use Medas\Console\Commands\{BaseConsoleCommand, CommandInput, ConsoleCommandGroup};
use Medas\Core\Attributes\Service;
use Medas\ServiceManager\Cache\{CacheManager, DirectoriesToClear};

/**
 * Console command `services:clear-caches` (alias `clear-cache`): clears every cache, including caches of other
 * processes that this process can only reach through their registered directories, like the web server's APCu cache.
 *
 * @sideEffect Empties all directories registered in `var/dirs-to-clear`, then clears every registered cache that
 *             implements `Clearable`.
 */
#[Service]
readonly class ClearCaches extends BaseConsoleCommand
{
    public function __construct(
        /** Clears the caches this process has registered. */
        private CacheManager       $cacheManager,

        /** Empties the cache directories registered by any process, so other processes start afresh too. */
        private DirectoriesToClear $directoriesToClear,

        /** The `services` group this command is listed under. */
        private Group              $group,
    )
    {
    }

    public function group(): ConsoleCommandGroup
    {
        return $this->group;
    }

    public function name(): string
    {
        return 'clear-caches';
    }

    public function aliases(): array
    {
        return ['clear-cache'];
    }

    public function description(): string
    {
        return 'Clears all the caches';
    }

    public function process(CommandInput $input): void
    {
        // Also clears what this process can't reach through its own caches, like the web server's APCu cache (through
        // its generation marker), the same way the post-install script does
        $this->directoriesToClear->clear();
        $this->cacheManager->clearAll();
    }
}
