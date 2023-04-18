<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\Core\Attributes\Service;

#[Service]
class MockServiceWithDefaultLogger
{
    public function __construct(
        private readonly Logger $logger,
    )
    {
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }
}
