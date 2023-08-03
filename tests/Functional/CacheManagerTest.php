<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Functional;

use Medas\Core\Caching\NoopCache;
use Medas\ServiceManager\Cache\CacheManager;
use PHPUnit\Framework\TestCase;

class CacheManagerTest extends TestCase
{
    public function testCreate(): void
    {
        $cache = service(CacheManager::class)->get();

        self::assertInstanceOf(NoopCache::class, $cache);
    }
}
