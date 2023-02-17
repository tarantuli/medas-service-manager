<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Functional;

use Medas\ServiceManager\Mapping\ImplementorFinder;
use Medas\ServiceManager\ParameterResolving\ParameterResolver;
use PHPUnit\Framework\TestCase;

class ImplementorFinderTest extends TestCase
{
    public function testFindImplementors(): void
    {
        self::assertInstanceOf(
            ParameterResolver::class,
            service(ImplementorFinder::class)->find(ParameterResolver::class)[0]
        );
    }
}
