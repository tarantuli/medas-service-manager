<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Cache\Interfaces;

interface PrimesCache
{
    public function primeCache(): void;
}
