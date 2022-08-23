<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Functional;

use Medas\ServiceManager\ParameterResolving\ParameterResolver;
use PHPUnit\Framework\TestCase;

class ImplementorFinderTest extends TestCase
{
    public function testFindImplementors(): void
    {
        self::assertInstanceOf(
            ParameterResolver::class,
            sm()->findImplementors(ParameterResolver::class)[0]
        );
    }
}
