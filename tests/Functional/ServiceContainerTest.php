<?php

namespace Medas\Test\Functional;

use Medas\ServiceContainer\Exceptions\ServiceNotFoundByTypeException;
use Medas\ServiceContainer\Interfaces\Logger;
use Medas\ServiceContainer\ServiceContainer;
use Medas\Test\MockUps\MockLogger1;
use Medas\Test\MockUps\MockLogger2;
use Medas\Test\MockUps\MockServiceWithDefaultLogger;
use Medas\Test\MockUps\MockServiceWithPreferredLogger;
use PHPUnit\Framework\TestCase;

final class ServiceContainerTest extends TestCase
{
    public function testCreateContainer(): void
    {
        $container = ServiceContainer::get();

        $this->assertInstanceOf(ServiceContainer::class, $container);
    }

    public function testServiceNotFound(): void
    {
        $container = ServiceContainer::get();

        $this->expectException(ServiceNotFoundByTypeException::class);
        $container->resolve(Logger::class);
    }

    public function testServiceIsFound(): void
    {
        $container = $this->loadMockUps();
        $service = $container->resolve(Logger::class);

        $this->assertInstanceOf(MockLogger2::class, $service);
    }

    private function loadMockUps(): ServiceContainer
    {
        $container = ServiceContainer::get();
        $container->addSourceDirectory(sprintf('%s/../MockUps', __DIR__));

        return $container;
    }

    public function testPreferredServiceIsFound(): void
    {
        $container = $this->loadMockUps();
        $service = $container->resolve(Logger::class, MockLogger1::class);

        $this->assertInstanceOf(MockLogger1::class, $service);
    }

    public function testServiceInstantiatedWithDefaultInjection(): void
    {
        $container = $this->loadMockUps();

        /** @var $service MockServiceWithDefaultLogger */
        $service = $container->resolve(MockServiceWithDefaultLogger::class);

        $this->assertInstanceOf(MockLogger2::class, $service->getLogger());
    }

    public function testServiceInstantiatedWithPreferredInjection(): void
    {
        $container = $this->loadMockUps();

        /** @var $service MockServiceWithPreferredLogger */
        $service = $container->resolve(MockServiceWithPreferredLogger::class);

        $this->assertInstanceOf(MockLogger1::class, $service->getLogger());
    }
}
