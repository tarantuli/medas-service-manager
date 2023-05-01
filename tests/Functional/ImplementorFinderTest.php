<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Functional;

use Medas\Core\Interfaces\CacheManager;
use Medas\ServiceManager\Mapping\ImplementorFinder;
use PHPUnit\Framework\TestCase;

class ImplementorFinderTest extends TestCase
{
    public function testFindImplementors(): void
    {
        self::assertInstanceOf(
            CacheManager::class,
            service(ImplementorFinder::class)->find(CacheManager::class)[0]
        );
    }
}
