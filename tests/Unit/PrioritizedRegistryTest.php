<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Unit;

use Medas\ServiceManager\Registry\PrioritizedRegistry;
use PHPUnit\Framework\TestCase;

final class PrioritizedRegistryTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Without a priority function — insertion order is preserved
    // -------------------------------------------------------------------------
    public function testAddReturnsTrue(): void
    {
        $registry = new PrioritizedRegistry();
        $item = new \stdClass();

        self::assertTrue($registry->add($item));
    }

    public function testAddReturnsFalseForDuplicate(): void
    {
        $registry = new PrioritizedRegistry();
        $item = new \stdClass();

        $registry->add($item);

        self::assertFalse($registry->add($item));
    }

    public function testDuplicateClassIsRejected(): void
    {
        $registry = new PrioritizedRegistry();
        $a = new \stdClass();
        $b = new \stdClass();

        $registry->add($a);

        // same class as $a
        $registry->add($b);

        self::assertSame([$a], $registry->all());
    }

    public function testInsertionOrderPreservedWithoutPriorityFn(): void
    {
        $registry = new PrioritizedRegistry();

        $first = new class
        {
        };

        $second = new class
        {
        };

        $third = new class
        {
        };

        $registry->add($first);
        $registry->add($second);
        $registry->add($third);

        self::assertSame([$first, $second, $third], $registry->all());
    }

    public function testAllReturnsEmptyArrayInitially(): void
    {
        self::assertSame([], new PrioritizedRegistry()->all());
    }

    public function testItemsSortedByPriorityDescending(): void
    {
        $registry = $this->makeRegistryWithPriority();

        $low    = new readonly class(1)  { public function __construct(public int $priority) {} };
        $high   = new readonly class(10) { public function __construct(public int $priority) {} };
        $medium = new readonly class(5)  { public function __construct(public int $priority) {} };

        $registry->add($low);
        $registry->add($high);
        $registry->add($medium);

        self::assertSame([$high, $medium, $low], $registry->all());
    }

    public function testNewItemInsertedInCorrectSortPosition(): void
    {
        $registry = $this->makeRegistryWithPriority();

        $low  = new readonly class(1)  { public function __construct(public int $priority) {} };
        $high = new readonly class(10) { public function __construct(public int $priority) {} };

        $registry->add($low);
        $registry->add($high);

        self::assertSame([$high, $low], $registry->all());
    }

    // -------------------------------------------------------------------------
    // With a priority function — items sorted highest-first
    // -------------------------------------------------------------------------
    private function makeRegistryWithPriority(): PrioritizedRegistry
    {
        return new PrioritizedRegistry(fn(object $item) => $item->priority); // priority is a typed property on the anonymous class
    }
}
