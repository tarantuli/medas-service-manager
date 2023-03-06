<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Cache;

use Medas\ServiceManager\Cache\Interfaces\Cache;

class NoopCache implements Cache
{
    public function get(array|string $key, callable $getter): mixed
    {
        return $getter();
    }

    public function set(array|string $key, mixed $value): void
    {
        // Do nothing
    }

    public function remove(array|string $key): void
    {
        // Do nothing
    }
}
