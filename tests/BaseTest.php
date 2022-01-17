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
        $manager->addPackage(new MockUpPackage());

        /*
         * DefaultLogger sorts later than AnotherLogger, and thus is registered as the default handler
         * for Logger interfaces
         */

        return $manager;
    }
}
