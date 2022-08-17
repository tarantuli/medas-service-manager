<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\Cache\Interfaces\PrimesCache;

#[Service]
class MockServicePrimingCache implements PrimesCache
{
    public function primeCache(): void
    {
        print('cache is primed');
    }
}
