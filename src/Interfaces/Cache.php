<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Interfaces;

interface Cache
{
    public function get(string|array $key, callable $getter): mixed;

    public function remove(string|array $key): void;
}
