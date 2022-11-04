<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Functional;

use Medas\ServiceManager\Exceptions\CouldNotResolveParameterException;
use Medas\ServiceManagerTest\BaseTest;
use Medas\ServiceManagerTest\MockUps\DefaultLogger;
use Medas\ServiceManagerTest\MockUps\Instantiation\ClassWithArgument;

class InstantiationTest extends BaseTest
{
    public function testBasicInstantiation(): void
    {
        $service = sm()->instantiate(DefaultLogger::class);

        self::assertInstanceOf(DefaultLogger::class, $service);
    }

    public function testNonServiceArgumentFail(): void
    {
        $this->loadMockUps();
        $this->expectException(CouldNotResolveParameterException::class);
        sm()->instantiate(ClassWithArgument::class);
    }

    public function testPassArgumentSuccess(): void
    {
        $this->loadMockUps();
        sm()->resetInstantiatingLog();
        $object = sm()->instantiate(ClassWithArgument::class, ['number' => 10]);
        self::assertInstanceOf(ClassWithArgument::class, $object);
    }
}
