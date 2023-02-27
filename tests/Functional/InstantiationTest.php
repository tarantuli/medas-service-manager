<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Functional;

use Medas\ServiceManager\Exceptions\CouldNotResolveParameter;
use Medas\ServiceManager\ServiceInstantiator;
use Medas\ServiceManagerTest\BaseTestClass;
use Medas\ServiceManagerTest\MockUps\DefaultLogger;
use Medas\ServiceManagerTest\MockUps\Instantiation\ClassWithArgument;

class InstantiationTest extends BaseTestClass
{
    public function testBasicInstantiation(): void
    {
        $service = sm()->instantiate(DefaultLogger::class);

        self::assertInstanceOf(DefaultLogger::class, $service);
    }

    public function testNonServiceArgumentFail(): void
    {
        $this->loadMockUps();
        $this->expectException(CouldNotResolveParameter::class);
        sm()->instantiate(ClassWithArgument::class);
    }

    public function testPassArgumentSuccess(): void
    {
        $this->loadMockUps();
        service(ServiceInstantiator::class)->resetInstantiatingStack();

        $object = sm()->instantiate(ClassWithArgument::class, ['number' => 10]);
        self::assertInstanceOf(ClassWithArgument::class, $object);
    }
}
