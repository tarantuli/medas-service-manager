<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

class ServiceMapping
{
    /** @var string[] */
    private array $mapping = [];

    /** @var bool[] */
    private array $hasSingularMapping = [];

    /** @var array[][] */
    private array $allImplementors = [];

    /** @var string[] */
    private array $classNames = [];

    /** @var bool[] Types bound with set(), which discovered services can't override */
    private array $boundTypes = [];

    private bool $classNamesNeedsRebuilding = false;

    /**
     * Binds a type to a class by hand. The binding wins over discovered services, whether they were discovered
     * before or after, and the class is added to the implementors of the type.
     *
     * @return bool Whether the mapping changed
     */
    public function set(string $type, string $className): bool
    {
        $this->boundTypes[$type] = true;

        $this->addImplementor($type, $className);

        if (($this->mapping[$type] ?? null) === $className) {
            return false;
        }

        $this->hasSingularMapping[$type] = true;
        $this->mapping[$type] = $className;
        $this->classNamesNeedsRebuilding = true;

        return true;
    }

    public function get(string $type): string
    {
        return $this->mapping[$type];
    }

    public function has(string $type): bool
    {
        return ($this->hasSingularMapping[$type] ?? false) === true;
    }

    public function hasMultiple(string $type): bool
    {
        return ($this->hasSingularMapping[$type] ?? null) === false;
    }

    public function getImplementors(string $type): array
    {
        return $this->allImplementors[$type] ?? [];
    }

    public function addFromPackage(array $mapping): void
    {
        foreach ($mapping as [$type, $className]) {
            // A type bound by hand keeps its binding; the discovered class only becomes one of its implementors
            if (isset($this->boundTypes[$type])) {
                $this->addImplementor($type, $className);

                continue;
            }

            if (!array_key_exists($type, $this->allImplementors)) {
                $this->allImplementors[$type] = [$className];
                $this->hasSingularMapping[$type] = true;
                $this->mapping[$type] = $className;
            }
            else {
                $this->allImplementors[$type][] = $className;
                $this->hasSingularMapping[$type] = false;
                $this->mapping[$type] = null;
            }
        }

        $this->classNamesNeedsRebuilding = true;
    }

    private function addImplementor(string $type, string $className): void
    {
        if (!in_array($className, $this->allImplementors[$type] ?? [], true)) {
            $this->allImplementors[$type][] = $className;
        }
    }

    public function getClassNames(): array
    {
        if ($this->classNamesNeedsRebuilding) {
            $this->classNames = array_unique(array_filter($this->mapping, fn($v) => $v !== null));
            $this->classNamesNeedsRebuilding = false;
        }

        return $this->classNames;
    }
}
