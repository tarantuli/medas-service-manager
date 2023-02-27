<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Functional;

use Medas\ServiceManager\Exceptions\ServiceNotFoundByType;
use Medas\ServiceManager\ServiceManager;
use Medas\ServiceManagerTest\BaseTestClass;
use Medas\ServiceManagerTest\MockUps\{AnotherLogger,
    DefaultLogger,
    Logger,
    MockServiceWithDefaultLogger,
    MockServiceWithNullOption,
    MockServiceWithPreferredLogger
};

final class ServiceManagerTest extends BaseTestClass
{
    public function testCreateManager(): void
    {
        $manager = ServiceManager::get();

        $this->assertInstanceOf(ServiceManager::class, $manager);
    }

    public function testServiceNotFound(): void
    {
        $manager = ServiceManager::get();

        $this->expectException(ServiceNotFoundByType::class);
        /** @noinspection PhpUndefinedClassInspection */
        $manager->resolve(NonExistingClass::class);
    }

    public function testServiceIsFound(): void
    {
        $manager = $this->loadMockUps();
        $service = $manager->resolve(Logger::class);

        $this->assertInstanceOf(DefaultLogger::class, $service);
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

    public function testServiceWithNullOption(): void
    {
        $manager = $this->loadMockUps();

        /** @var $service MockServiceWithNullOption */
        $service = $manager->resolve(MockServiceWithNullOption::class);

        $this->assertInstanceOf(MockServiceWithNullOption::class, $service);
    }

    public function testCachePriming(): void
    {
        $manager = $this->loadMockUps();
        ob_start();
        $manager->primeCaches();
        $output = ob_get_clean();

        self::assertEquals('cache is primed', $output);
    }
}
