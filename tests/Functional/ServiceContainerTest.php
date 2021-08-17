<?php

namespace Medas\Test\Functional;

use Medas\ServiceContainer\Exceptions\ServiceNotFoundByTypeException;
use Medas\ServiceContainer\Interfaces\Logger;
use Medas\ServiceContainer\ServiceContainer;
use Medas\Test\MockUps\MockLogger;
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

        self::assertInstanceOf(MockLogger::class, $service);
    }
}
