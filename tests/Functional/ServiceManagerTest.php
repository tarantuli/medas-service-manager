<?php

declare(strict_types=1);

namespace Medas\Test\Functional;

use Medas\ServiceManager\Exceptions\ServiceNotFoundByTypeException;
use Medas\ServiceManager\ServiceManager;
use Medas\Test\MockUps\AnotherLogger;
use Medas\Test\MockUps\DefaultLogger;
use Medas\Test\MockUps\Logger;
use Medas\Test\MockUps\MockServiceWithDefaultLogger;
use Medas\Test\MockUps\MockServiceWithConfigInjection;
use Medas\Test\MockUps\MockServiceWithPreferredLogger;
use PHPUnit\Framework\TestCase;

final class ServiceManagerTest extends TestCase
{
    public function testCreateManager(): void
    {
        $manager = ServiceManager::get();

        $this->assertInstanceOf(ServiceManager::class, $manager);
    }

    public function testServiceNotFound(): void
    {
        $manager = ServiceManager::get();

        $this->expectException(ServiceNotFoundByTypeException::class);
        $manager->resolve(Logger::class);
    }

    public function testServiceIsFound(): void
    {
        $manager = $this->loadMockUps();
        $service = $manager->resolve(Logger::class);

        $this->assertInstanceOf(DefaultLogger::class, $service);
    }

    private function loadMockUps(): ServiceManager
    {
        $manager = ServiceManager::get();
        $manager->addPackage(AnotherLogger::class);

        /*
         * DefaultLogger sorts later than AnotherLogger, and thus is registered as the default handler
         * for Logger interfaces
         */

        return $manager;
    }

    public function testServiceInstantiatedWithDefaultInjection(): void
    {
        $manager = $this->loadMockUps();

        /** @var $service MockServiceWithDefaultLogger */
        $service = $manager->resolve(MockServiceWithDefaultLogger::class);

        $this->assertInstanceOf(DefaultLogger::class, $service->getLogger());
    }

    public function testServiceInstantiatedWithPreferredInjection(): void
    {
        $manager = $this->loadMockUps();

        /** @var $service MockServiceWithPreferredLogger */
        $service = $manager->resolve(MockServiceWithPreferredLogger::class);

        $this->assertInstanceOf(AnotherLogger::class, $service->getLogger());
    }

    public function testServiceWithConfigInjection(): void
    {
        $manager = $this->loadMockUps();

        /** @var $service MockServiceWithConfigInjection */
        $service = $manager->resolve(MockServiceWithConfigInjection::class);

        $this->assertEquals('service-manager', $service->getProject());
    }
}
