<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Functional;

use Medas\ServiceManager\Cache\CacheManager;
use Medas\ServiceManager\Cache\NoopCache;
use Medas\ServiceManager\Interfaces\Cache;
use PHPUnit\Framework\TestCase;

class CacheManagerTest extends TestCase
{
    public function testCreate(): void
    {
        $cache = service(CacheManager::class)->create(NoopCache::class, $this::class);

        self::assertInstanceOf(Cache::class, $cache);
    }
}
