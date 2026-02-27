<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\Core\Interfaces\Cache;

class MockCache implements Cache
{
    public function get(array|string $key, callable $getter, int $ttl = 0): mixed
    {
        return $getter();
    }

    public function set(array|string $key, mixed $value, int $ttl = 0): void
    {
    }

    public function remove(array|string $key): void
    {
    }

    public function contains(array|string $key): bool
    {
        return false;
    }
}
