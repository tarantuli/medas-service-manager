<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Functional;

use Medas\ServiceManager\Cache\CacheManager;
use Medas\ServiceManager\Cache\NoopCache;
use PHPUnit\Framework\TestCase;

class CacheManagerTest extends TestCase
{
    public function testCreate(): void
    {
        $cache = service(CacheManager::class)->get();

        self::assertInstanceOf(NoopCache::class, $cache);
    }
}
