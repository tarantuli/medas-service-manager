<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\Core\Attributes\Service;

#[Service]
readonly class MockServiceWithDefaultLogger
{
    public function __construct(
        private Logger $logger,
    )
    {
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }
}
