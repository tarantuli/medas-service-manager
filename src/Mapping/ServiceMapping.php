<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

use Medas\ServiceManager\Exceptions\MultipleImplementorsFound;

class ServiceMapping
{
    /** @var string[] */
    private array $mapping = [];

    /** @var bool[] */
    private array $hasSingularMapping = [];

    /** @var array[] */
    private array $allImplementors = [];

    public function set(string $type, string $className): bool
    {
        if ($this->mapping[$type] ?? null === $className) {
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
        if (!array_key_exists($type, $this->hasSingularMapping)) {
            return false;
        }

        if ($this->hasSingularMapping[$type] === false) {
            throw new MultipleImplementorsFound($type, $this->allImplementors[$type]);
        }

        return true;
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
        return array_filter($this->mapping);
    }
}
