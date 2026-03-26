<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ConsoleCommands;

use Medas\Console\Commands\{BaseConsoleCommand, CommandInput, ConsoleCommandGroup};
use Medas\Core\Attributes\Service;
use Medas\ServiceManager\Cache\CacheManager;

#[Service]
readonly class ClearCaches extends BaseConsoleCommand
{
    public function __construct(
        private CacheManager $cacheManager,
        private Group        $group,
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
        $this->cacheManager->clearAll();
    }
}
