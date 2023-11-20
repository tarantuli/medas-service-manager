<?php

declare(strict_types=1);

namespace Medas\ServiceManager\DataTree;

use Medas\ServiceManager\Exceptions\IndexNotFoundInDataTree;

class DataTree
{
    private array $values = [];

    /** @var History[] */
    private array $history = [];

    private mixed $lastFoundValue;

    /** @var History[] */
    private array $lastFoundHistory;

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

    private function indexToPath(string $index): array
    {
        return explode('.', $index);
    }

    public function mergeArray(array $values, string $source = 'runtime'): self
    {
        return $this->setRecursively($values, $source);
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

    public function has(string $index): bool
    {
        $path = $this->indexToPath($index);
        $values = &$this->values;
        $history = $this->history;

        foreach ($path as $subPath) {
            if (!isset($values[$subPath])) {
                return false;
            }

            $values = &$values[$subPath];
            $history = $history[$subPath];
        }

        $this->lastFoundValue = $values;
        $this->lastFoundHistory = $history;

        return true;
    }

    public function mergeDefaults(array $values): self
    {
        return $this->setRecursively($values, source: 'defaults', doOverwrite: false);
    }

    public function get(string $index): mixed
    {
        if (!$this->has($index)) {
            throw new IndexNotFoundInDataTree($index);
        }

        return $this->lastFoundValue;
    }

    /** @return History[] */
    public function getHistory(string $index): array
    {
        if (!$this->has($index)) {
            throw new IndexNotFoundInDataTree($index);
        }

        return $this->lastFoundHistory;
    }

    public function getSource(string $index): string
    {
        if (!$this->has($index)) {
            throw new IndexNotFoundInDataTree($index);
        }

        return $this->lastFoundHistory[count($this->lastFoundHistory) - 1]->source;
    }

    public function getElse(string $index, mixed $fallback): mixed
    {
        if (!$this->has($index)) {
            return $fallback;
        }

        return $this->lastFoundValue;
    }
}
