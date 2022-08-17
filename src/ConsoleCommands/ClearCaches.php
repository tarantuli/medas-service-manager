<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ConsoleCommands;

use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\Cache\CacheManager;
use Medas\ServiceManager\Console\{BaseConsoleCommand, ConsoleCommandGroup};

#[Service]
class ClearCaches extends BaseConsoleCommand
{
    public function __construct(
        private readonly Group        $group,
        private readonly CacheManager $cacheManager,
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

    public function description(): string
    {
        return 'Clears all the caches';
    }

    public function process(array $arguments)
    {
        $this->cacheManager->clearAll();
    }
}
