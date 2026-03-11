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

    private array $itemNames = [];

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

    public function __serialize(): array
    {
        return [
            'sortByPriority' => $this->sortByPriority,
            'itemNames' => array_map(fn($item) => $item::class, $this->items),
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->sortByPriority = $data['sortByPriority'];
        $this->itemNames = $data['itemNames'];
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

        if ($this->sortByPriority) {
            usort($this->items, fn(object $a, object $b) => -($a->priority() <=> $b->priority()));
        }

        return true;
    }

    /**
     * @return T[]
     */
    public function all(): array
    {
        if ($this->itemNames && !$this->items) {
            foreach ($this->itemNames as $itemName) {
                $this->items[] = service($itemName);
            }
        }

        return $this->items;
    }
}
