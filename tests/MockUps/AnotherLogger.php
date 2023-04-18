<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\Core\Attributes\Service;

#[Service]
class AnotherLogger implements Logger
{
    public function test()
    {
    }
}
