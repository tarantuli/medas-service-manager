<?php

namespace Medas\Test\Functional;

use Medas\ServiceContainer\Exceptions\ServiceNotFoundByTypeException;
use Medas\ServiceContainer\Interfaces\Logger;
use Medas\ServiceContainer\ServiceContainer;
use Medas\Test\MockUps\MockLogger;
use Medas\Test\MockUps\MockServiceWithDefaultLogger;
use Medas\Test\MockUps\MockServiceWithPreferredLogger;
use Medas\Test\MockUps\SecondLevel\SecondLevelMockLogger;
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
        $container = ServiceContainer::get();
        $container->addSourceDirectory(sprintf('%s/../MockUps', __DIR__));

        $service = $container->resolve(Logger::class);

        $this->assertInstanceOf(SecondLevelMockLogger::class, $service);
    }

    public function testPreferredServiceIsFound(): void
    {
        $container = ServiceContainer::get();
        $container->addSourceDirectory(sprintf('%s/../MockUps', __DIR__));

        $service = $container->resolve(Logger::class, MockLogger::class);

        $this->assertInstanceOf(MockLogger::class, $service);
    }

    public function testServiceInstantiatedWithDefaultInjection(): void
    {
        $container = ServiceContainer::get();
        $container->addSourceDirectory(sprintf('%s/../MockUps', __DIR__));

        /**
         * @var $service MockServiceWithDefaultLogger
         */
        $service = $container->resolve(MockServiceWithDefaultLogger::class);

        $this->assertInstanceOf(SecondLevelMockLogger::class, $service->getLogger());
    }

    public function testServiceInstantiatedWithPreferredInjection(): void
    {
        $container = ServiceContainer::get();
        $container->addSourceDirectory(sprintf('%s/../MockUps', __DIR__));

        /**
         * @var $service MockServiceWithDefaultLogger
         */
        $service = $container->resolve(MockServiceWithPreferredLogger::class);

        $this->assertInstanceOf(MockLogger::class, $service->getLogger());
    }
}
