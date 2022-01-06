<?php

declare(strict_types=1);

namespace Medas\Test;

use Medas\ServiceManager\ServiceManager;
use Medas\Test\MockUps\MockUpPackage;
use PHPUnit\Framework\TestCase;

class BaseTest extends TestCase
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
