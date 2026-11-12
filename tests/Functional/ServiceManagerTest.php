<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Functional;

use Medas\Core\Exceptions\ServiceNotFoundByType;
use Medas\ServiceManager\{Cache\CacheManager, CachePrimer, ServiceManager};
use Medas\ServiceManagerTest\MockUps\{
    AnotherLogger,
    DefaultLogger,
    Logger,
    MockCache,
    MockServiceDependingOnItself,
    MockServiceWithDefaultLogger,
    MockServiceWithNullOption,
    MockServiceWithPreferredLogger
};

final class ServiceManagerTest extends BaseTestClass
{
    public function testCreateManager(): void
    {
        $this->assertInstanceOf(ServiceManager::class, $this->serviceManager());
    }

    public function testServiceNotFound(): void
    {
        $this->expectException(ServiceNotFoundByType::class);

        /** @noinspection PhpUndefinedClassInspection */
        $this->serviceManager()->resolve(NonExistingClass::class);
    }

    public function testServiceIsFound(): void
    {
        $service = $this->serviceManager()->resolve(Logger::class);

        $this->assertInstanceOf(DefaultLogger::class, $service);
    }

    public function testServiceInstantiatedWithDefaultInjection(): void
    {
        /** @var $service MockServiceWithDefaultLogger */
        $service = $this->serviceManager()->resolve(MockServiceWithDefaultLogger::class);

        $this->assertInstanceOf(DefaultLogger::class, $service->getLogger());
    }

    public function testServiceInstantiatedWithPreferredInjection(): void
    {
        /** @var $service MockServiceWithPreferredLogger */
        $service = $this->serviceManager()->resolve(MockServiceWithPreferredLogger::class);

        $this->assertInstanceOf(AnotherLogger::class, $service->getLogger());
    }

    public function testServiceWithNullOption(): void
    {
        /** @var $service MockServiceWithNullOption */
        $service = $this->serviceManager()->resolve(MockServiceWithNullOption::class);

        $this->assertInstanceOf(MockServiceWithNullOption::class, $service);
    }

    public function testServiceDependingOnItselfHasOneInstance(): void
    {
        $manager = $this->serviceManager();

        /** @var $service MockServiceDependingOnItself */
        $service = $manager->resolve(MockServiceDependingOnItself::class);

        $this->assertSame($service, $service->itself());
        $this->assertSame($service, $manager->resolve(MockServiceDependingOnItself::class));
    }

    public function testCachePriming(): void
    {
        $manager = $this->serviceManager();
        $cacheManager = $manager->resolve(CacheManager::class);

        // Priming only happens with a persistent cache. A name of its own leaves the 'default' cache to other tests.
        $cacheManager->register(new MockCache(), 'mock');

        $this->expectOutputString('cache is primed');

        new CachePrimer($manager, $cacheManager)->prime();
    }
}
