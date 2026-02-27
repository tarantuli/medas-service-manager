<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Functional;

use Medas\Core\Interfaces\CacheManager;
use Medas\ServiceManager\{Exceptions\ServiceNotFoundByType, ServiceManager};
use Medas\ServiceManagerTest\MockUps\{
    AnotherLogger,
    DefaultLogger,
    Logger,
    MockCache,
    MockServiceWithDefaultLogger,
    MockServiceWithNullOption,
    MockServiceWithPreferredLogger
};

final class ServiceManagerTest extends BaseTestClass
{
    public function testCreateManager(): void
    {
        $manager = medas()->serviceManager();

        $this->assertInstanceOf(ServiceManager::class, $manager);
    }

    public function testServiceNotFound(): void
    {
        $manager = medas()->serviceManager();

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

        $manager->resolve(CacheManager::class)->register(new MockCache());

        ob_start();

        $manager->cachePrimer->prime();

        $output = ob_get_clean();

        self::assertEquals('cache is primed', $output);
    }
}
