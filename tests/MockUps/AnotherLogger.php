<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\ServiceManager\Attributes\Service;

#[Service]
class AnotherLogger implements Logger
{
    public function test()
    {
    }
}
