<?php

declare(strict_types=1);

namespace Medas\Test\MockUps;

use Medas\ServiceManager\Attributes\Service;

#[Service]
class MockServiceWithDefaultLogger
{
    public function __construct(private Logger $logger)
    {
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }
}
