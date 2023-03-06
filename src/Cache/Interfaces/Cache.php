<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Cache\Interfaces;

interface Cache
{
    public function get(string|array $key, callable $getter): mixed;

    public function set(string|array $key, mixed $value): void;

    public function remove(string|array $key): void;
}
