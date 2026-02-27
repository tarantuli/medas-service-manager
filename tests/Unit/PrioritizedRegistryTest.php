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
        $registry->add($b); // same class as $a

        self::assertSame([$a], $registry->all());
    }

    public function testInsertionOrderPreservedWithoutPriorityFn(): void
    {
        $registry = new PrioritizedRegistry();

        $first  = new class {};
        $second = new class {};
        $third  = new class {};

        $registry->add($first);
        $registry->add($second);
        $registry->add($third);

        self::assertSame([$first, $second, $third], $registry->all());
    }

    public function testAllReturnsEmptyArrayInitially(): void
    {
        self::assertSame([], new PrioritizedRegistry()->all());
    }

    // -------------------------------------------------------------------------
    // With a priority function — items sorted highest-first
    // -------------------------------------------------------------------------

    private function makeRegistryWithPriority(): PrioritizedRegistry
    {
        return new PrioritizedRegistry(fn(object $item) => $item->priority);
    }

    public function testItemsSortedByPriorityDescending(): void
    {
        $registry = $this->makeRegistryWithPriority();

        $low    = (object) ['priority' => 1];
        $high   = (object) ['priority' => 10];
        $medium = (object) ['priority' => 5];

        $registry->add($low, true);
        $registry->add($high, true);
        $registry->add($medium, true);

        self::assertSame([$high, $medium, $low], $registry->all());
    }

    public function testNewItemInsertedInCorrectSortPosition(): void
    {
        $registry = $this->makeRegistryWithPriority();

        $low  = (object) ['priority' => 1];
        $high = (object) ['priority' => 10];

        $registry->add($low, true);
        $registry->add($high, true);

        self::assertSame([$high, $low], $registry->all());
    }
}
