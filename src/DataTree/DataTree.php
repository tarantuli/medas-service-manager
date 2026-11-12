<?php

declare(strict_types=1);

namespace Medas\ServiceManager\DataTree;

use Medas\ServiceManager\Exceptions\{HistoryNotAvailableInDataTree, IndexNotFoundInDataTree};

/**
 * A nested array of values, addressed by dot-separated indexes like `database.host`. A dot that's part of a key is
 * escaped with a backslash: `paths.var\.log`.
 *
 * An index can also point to a branch, which is then returned as an array.
 *
 * Every write records its source, like `'defaults'` or `'runtime'`, so you can find out where a value came from.
 *
 * @state Records the history of every value until the tree is serialized. A tree restored from serialized data only
 *        has its values, so `getHistory()` and `getSource()` throw.
 * @performance Parsed indexes are memoized in a static cache shared by all trees, which grows with every distinct
 *              index.
 */
class DataTree
{
    /** @var array<string, string[]> Memoised parse results — the output depends only on the input string. */
    private static array $pathCache = [];

    private array $values = [];

    /** @var History[] Debug-only record of which sources wrote each value; never serialized. */
    private array $history = [];

    /** False once restored from serialized form: history from before that point is gone, so a partial record would mislead. */
    private bool $recordsHistory = true;

    /**
     * @param array $defaults Values by index, recorded with `'defaults'` as their source.
     */
    public function __construct(array $defaults = [])
    {
        foreach ($defaults as $index => $value) {
            $this->set($index, $value, 'defaults');
        }
    }

    public function __serialize(): array
    {
        return ['values' => $this->values];
    }

    public function __unserialize(array $data): void
    {
        $this->values = $data['values'];
        $this->history = [];
        $this->recordsHistory = false;
    }

    public function set(string $index, mixed $value, string $source = 'runtime'): self
    {
        $path = $this->indexToPath($index);
        $values = &$this->values;
        $history = &$this->history;
        $lastPath = array_pop($path);

        foreach ($path as $subPath) {
            if (!array_key_exists($subPath, $values)) {
                $values[$subPath] = [];
                $history[$subPath] = [];
            }

            $values = &$values[$subPath];
            $history = &$history[$subPath];
        }

        $values[$lastPath] = $value;

        if ($this->recordsHistory) {
            $history[$lastPath][] = new History($source, $value);
        }

        return $this;
    }

    /**
     * Sets every leaf of the nested array `$values`, overwriting existing values.
     */
    public function mergeArray(array $values, string $source = 'runtime'): self
    {
        return $this->setRecursively($values, $source);
    }

    /**
     * A stored `null` counts as present.
     */
    public function has(string $index): bool
    {
        return $this->find($index) !== null;
    }

    /**
     * Sets every leaf of the nested array `$values` that has no value yet, with `'defaults'` as the source.
     */
    public function mergeDefaults(array $values): self
    {
        return $this->setRecursively($values, source: 'defaults', doOverwrite: false);
    }

    private function setRecursively(
        array  $values,
        string $source = 'runtime',
        string $path = '',
        bool   $doOverwrite = true
    ): self
    {
        foreach ($values as $index => $value) {
            $index = $path === '' ? $index : $path . '.' . $index;

            if (is_array($value)) {
                $this->setRecursively($value, $source, $index, $doOverwrite);
            }
            elseif ($doOverwrite || !$this->has($index)) {
                $this->set($index, $value, $source);
            }
        }

        return $this;
    }

    /**
     * @throws IndexNotFoundInDataTree When nothing is stored at the index.
     */
    public function get(string $index): mixed
    {
        $result = $this->find($index);

        if ($result === null) {
            throw new IndexNotFoundInDataTree($index);
        }

        return $result[0];
    }

    /**
     * @return History[] Every write to the index, oldest first.
     *
     * @throws IndexNotFoundInDataTree When nothing is stored at the index.
     * @throws HistoryNotAvailableInDataTree When the tree was restored from serialized data.
     */
    public function getHistory(string $index): array
    {
        if ($this->find($index) === null) {
            throw new IndexNotFoundInDataTree($index);
        }

        if (!$this->recordsHistory) {
            throw new HistoryNotAvailableInDataTree($index);
        }

        $history = $this->history;

        foreach ($this->indexToPath($index) as $subPath) {
            $history = $history[$subPath];
        }

        return $history;
    }

    /**
     * The source of the last write to the index.
     *
     * @throws IndexNotFoundInDataTree When nothing is stored at the index.
     * @throws HistoryNotAvailableInDataTree When the tree was restored from serialized data.
     */
    public function getSource(string $index): string
    {
        $history = $this->getHistory($index);

        return $history[array_key_last($history)]->source;
    }

    /**
     * Returns `$fallback` when nothing is stored at the index. A stored `null` is returned as `null`.
     */
    public function getElse(string $index, mixed $fallback): mixed
    {
        $found = $this->find($index);

        return $found !== null ? $found[0] : $fallback;
    }

    /**
     * Traverses the tree for $index and returns [value], or null when not found.
     * The value is wrapped so that a stored null can be told apart from "not found".
     *
     * @return array{0: mixed}|null
     */
    private function find(string $index): array|null
    {
        $values = $this->values;

        foreach ($this->indexToPath($index) as $subPath) {
            // A scalar leaf has no children, so a deeper index is simply not found
            if (!is_array($values) || !array_key_exists($subPath, $values)) {
                return null;
            }

            // By-value walk: references would force copy-on-write separation of shared subtrees
            $values = $values[$subPath];
        }

        return [$values];
    }

    private function indexToPath(string $index): array
    {
        if (isset(self::$pathCache[$index])) {
            return self::$pathCache[$index];
        }

        // Escaped dots are rare; without a backslash a plain split is equivalent and runs in C
        if (!str_contains($index, '\\')) {
            return self::$pathCache[$index] = explode('.', $index);
        }

        $paths = [];
        $stack = '';
        $nextIsLiteral = false;
        $len = strlen($index);

        for ($i = 0; $i < $len; $i++) {
            if ($nextIsLiteral) {
                $stack .= $index[$i];
                $nextIsLiteral = false;

                continue;
            }

            if ($index[$i] === '.') {
                $paths[] = $stack;
                $stack = '';

                continue;
            }

            if ($index[$i] === '\\') {
                $nextIsLiteral = true;

                continue;
            }

            $stack .= $index[$i];
        }

        $paths[] = $stack;

        return self::$pathCache[$index] = $paths;
    }
}
