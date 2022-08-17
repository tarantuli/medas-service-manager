<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Functional;

use Medas\ServiceManager\Console\ConsoleCommand;
use PHPUnit\Framework\TestCase;

class ImplementorFinderTest extends TestCase
{
    public function testFindImplementors(): void
    {
        self::assertInstanceOf(
            ConsoleCommand::class,
            sm()->findImplementors(ConsoleCommand::class)[0]
        );
    }
}
