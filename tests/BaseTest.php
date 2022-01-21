<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest;

use Medas\ServiceManager\ServiceManager;
use Medas\ServiceManagerTest\MockUps\MockUpPackage;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    protected function loadMockUps(): ServiceManager
    {
        $manager = ServiceManager::get();
        $manager->addPackage(MockUpPackage::instance());

        return $manager;
    }
}
