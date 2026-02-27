<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\Core\Attributes\{PreferredDefault, Service};

#[Service]
readonly class MockServiceWithPreferredLogger
{
    public function __construct(
        #[PreferredDefault(AnotherLogger::class)]
        private Logger $logger,
    )
    {
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }
}
