<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

class ServiceMapping
{
    /** @var string[] */
    private array $mapping = [];

    public function set(string $type, string $className): bool
    {
        if ($this->mapping[$type] ?? null === $className) {
            return false;
        }

        $this->mapping[$type] = $className;
        return true;
    }

    public function get(string $type): string
    {
        return $this->mapping[$type];
    }

    public function has(string $type): bool
    {
        return array_key_exists($type, $this->mapping);
    }

    public function add(array $mapping): void
    {
        foreach ($mapping as $type => $className) {
            $this->mapping[$type] = $className;
        }
    }

    public function getAll(): array
    {
        return array_unique($this->mapping);
    }
}
