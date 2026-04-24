<?php

declare(strict_types=1);

namespace Medas\ServiceManager\DataTree;

use Medas\ServiceManager\Exceptions\IndexNotFoundInDataTree;

class DataTree
{
    /** @var array<string, string[]> Memoised parse results — the output depends only on the input string. */
    private static array $pathCache = [];

    private array $values = [];

    /** @var History[] */
    private array $history = [];

    public function __construct(array $defaults = [])
    {
        foreach ($defaults as $index => $value) {
            $this->set($index, $value, 'default');
        }
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
        $history[$lastPath][] = new History($source, $value);

        return $this;
    }

    public function mergeArray(array $values, string $source = 'runtime'): self
    {
        return $this->setRecursively($values, $source);
    }

    public function has(string $index): bool
    {
        return $this->find($index) !== null;
    }

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

    public function get(string $index): mixed
    {
        $result = $this->find($index);

        if ($result === null) {
            throw new IndexNotFoundInDataTree($index);
        }

        return $result[0];
    }

    /** @return History[] */
    public function getHistory(string $index): array
    {
        $result = $this->find($index);

        if ($result === null) {
            throw new IndexNotFoundInDataTree($index);
        }

        return $result[1];
    }

    public function getSource(string $index): string
    {
        $result = $this->find($index);

        if ($result === null) {
            throw new IndexNotFoundInDataTree($index);
        }

        return $result[1][array_key_last($result[1])]->source;
    }

    public function getElse(string $index, mixed $fallback): mixed
    {
        $found = $this->find($index);

        return $found !== null ? $found[0] : $fallback;
    }

    /**
     * Traverses the tree for $index and returns a [value, history] pair, or null when not found.
     * All public accessors delegate here instead of relying on shared mutable state.
     *
     * @return array{0: mixed, 1: History[]}|null
     */
    private function find(string $index): array|null
    {
        $path = $this->indexToPath($index);
        $values = &$this->values;
        $history = $this->history;

        foreach ($path as $subPath) {
            if (!array_key_exists($subPath, $values)) {
                return null;
            }

            $values = &$values[$subPath];
            $history = $history[$subPath];
        }

        return [$values, $history];
    }

    private function indexToPath(string $index): array
    {
        if (isset(self::$pathCache[$index])) {
            return self::$pathCache[$index];
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
