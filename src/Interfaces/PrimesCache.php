<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Interfaces;

interface PrimesCache
{
    public function primeCache(): void;
}
