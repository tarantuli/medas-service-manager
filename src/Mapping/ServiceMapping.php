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

    public function set(string $type, string $className): bool
    {
        if (($this->mapping[$type] ?? null) === $className) {
            return false;
        }

        $this->hasSingularMapping[$type] = true;
        $this->mapping[$type] = $className;

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

    public function addFromPackage(array $mapping): void
    {
        foreach ($mapping as [$type, $className]) {
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
    }

    public function getAll(): array
    {
        return array_filter($this->mapping, fn($v) => $v !== null);
    }
}
