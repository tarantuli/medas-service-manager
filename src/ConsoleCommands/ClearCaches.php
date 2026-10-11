<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ConsoleCommands;

use Medas\Console\Commands\{BaseConsoleCommand, CommandInput, ConsoleCommandGroup};
use Medas\Core\Attributes\Service;
use Medas\ServiceManager\Cache\{CacheManager, DirectoriesToClear};

#[Service]
readonly class ClearCaches extends BaseConsoleCommand
{
    public function __construct(
        private CacheManager       $cacheManager,
        private DirectoriesToClear $directoriesToClear,
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
