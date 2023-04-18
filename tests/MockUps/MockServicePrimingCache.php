<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\Core\Attributes\Service;
use Medas\Core\Interfaces\PrimesCache;

#[Service]
class MockServicePrimingCache implements PrimesCache
{
    public function primeCache(): void
    {
        print('cache is primed');
    }
}
