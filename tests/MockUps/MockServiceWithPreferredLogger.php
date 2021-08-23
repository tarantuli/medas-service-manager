<?php

namespace Medas\Test\MockUps;

use Medas\ServiceContainer\Attributes\PreferredClass;
use Medas\ServiceContainer\Attributes\Service;
use Medas\ServiceContainer\Interfaces\Logger;

#[Service]
class MockServiceWithPreferredLogger
{
    #[PreferredClass(Logger::class, AnotherLogger::class)]
    public function __construct(private Logger $logger)
    {
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }
}
