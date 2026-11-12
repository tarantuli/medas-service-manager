<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Functional;

use Medas\Core\Interfaces\CacheManager;
use Medas\ServiceManager\{Cache\CacheManager as CacheManagerService, Mapping\ImplementorFinder};
use PHPUnit\Framework\TestCase;

class ImplementorFinderTest extends TestCase
{
    public function testFindImplementors(): void
    {
        // find() returns class names, not instances
        self::assertContains(
            CacheManagerService::class,
            service(ImplementorFinder::class)->find(CacheManager::class)
        );
    }
}
