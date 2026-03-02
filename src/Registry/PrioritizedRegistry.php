<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Registry;

/**
 * A simple registry that deduplicates items by class name and optionally keeps them sorted by a priority function.
 *
 * Deduplication is always applied: adding an item whose class is already registered is a no-op.
 * When a $priorityFn is supplied, the list is re-sorted (the highest value first) after every insertion.
 *
 * @template T of object
 */
class PrioritizedRegistry
{
    /** @var T[] */
    private array $items = [];

    /**
     * @param bool $sortByPriority
     *     When true, items are kept sorted from highest to lowest priority.
     *     When false, the insertion order is preserved.
     */
    public function __construct(
        private readonly bool $sortByPriority = true,
    )
    {
    }

    /**
     * Adds an item to the registry.
     *
     * Returns true when the item was added, false when an item of the same class was already registered.
     *
     * @param T $item
     */
    public function add(object $item): bool
    {
        if (array_any($this->items, fn(object $existing) => $existing::class === $item::class)) {
            return false;
        }

        $this->items[] = $item;

        if ($this->sortByPriority !== null) {
            usort($this->items, fn(object $a, object $b) => -($a->priority() <=> $b->priority()));
        }

        return true;
    }

    /**
     * @return T[]
     */
    public function all(): array
    {
        return $this->items;
    }
}
