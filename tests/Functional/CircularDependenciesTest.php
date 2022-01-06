<?php

declare(strict_types=1);

namespace Medas\Test\Functional;

use Medas\ServiceManager\Exceptions\CircularDependencyException;
use Medas\Test\BaseTest;
use Medas\Test\MockUps\CircularDependencies\DirectDependency1;
use Medas\Test\MockUps\CircularDependencies\IndirectDependency1;

class CircularDependenciesTest extends BaseTest
{
    public function testDirectDependency(): void
    {
        $manager = $this->loadMockUps();
        self::expectException(CircularDependencyException::class);
        $manager->resolve(DirectDependency1::class);
    }

    public function testIndirectDependency(): void
    {
        $manager = $this->loadMockUps();
        self::expectException(CircularDependencyException::class);
        $manager->resolve(IndirectDependency1::class);
    }
}
