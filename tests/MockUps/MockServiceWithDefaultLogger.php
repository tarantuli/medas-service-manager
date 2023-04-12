<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\ServiceManager\Service;

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
