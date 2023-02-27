<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Functional;

use Medas\ServiceManager\Exceptions\CircularDependencyFound;
use Medas\ServiceManagerTest\BaseTestClass;
use Medas\ServiceManagerTest\MockUps\CircularDependencies\DirectDependency1;
use Medas\ServiceManagerTest\MockUps\CircularDependencies\IndirectDependency1;

class CircularDependenciesTest extends BaseTestClass
{
    public function testDirectDependency(): void
    {
        $manager = $this->loadMockUps();
        self::expectException(CircularDependencyFound::class);
        $manager->resolve(DirectDependency1::class);
    }

    public function testIndirectDependency(): void
    {
        $manager = $this->loadMockUps();
        self::expectException(CircularDependencyFound::class);
        $manager->resolve(IndirectDependency1::class);
    }
}
