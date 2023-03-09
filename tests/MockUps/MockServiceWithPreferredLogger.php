<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\ServiceManager\Attributes\{PreferredDefault, Service};

#[Service]
class MockServiceWithPreferredLogger
{
    public function __construct(
        #[PreferredDefault(AnotherLogger::class)]
        private readonly Logger $logger,
    )
    {
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }
}
