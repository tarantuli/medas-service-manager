<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Functional;

use Medas\ServiceManager\Exceptions\CircularDependencyException;
use Medas\ServiceManagerTest\BaseTest;
use Medas\ServiceManagerTest\MockUps\CircularDependencies\DirectDependency1;
use Medas\ServiceManagerTest\MockUps\CircularDependencies\IndirectDependency1;

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
