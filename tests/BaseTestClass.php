<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest;

use Medas\ServiceManager\ServiceManager;
use Medas\ServiceManagerTest\MockUps\MockUpPackage;
use PHPUnit\Framework\TestCase;

abstract class BaseTestClass extends TestCase
{
    protected function loadMockUps(): ServiceManager
    {
        $manager = ServiceManager::get();
        $manager->config()->addPackage(MockUpPackage::instance(), true);

        return $manager;
    }
}
