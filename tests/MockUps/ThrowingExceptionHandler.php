<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\Core\{Attributes\Service, Interfaces\ExceptionHandler};

/**
 * An exception handler that fails itself, like a mailer that can't reach its server.
 */
#[Service]
readonly class ThrowingExceptionHandler implements ExceptionHandler
{
    public function handleException(\Throwable $exception): bool
    {
        throw new \RuntimeException('the handler itself failed');
    }
}
