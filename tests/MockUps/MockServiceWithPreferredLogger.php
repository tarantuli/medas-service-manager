<?php

declare(strict_types=1);

namespace Medas\Test\MockUps;

use Medas\ServiceManager\Attributes\PreferredClass;
use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\Interfaces\Logger;

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
