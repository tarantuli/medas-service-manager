<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\ServiceManager\ErrorHandling\ExceptionHandlerManager;

/**
 * Keeps the reports instead of writing them to `STDERR`.
 */
class CapturingExceptionHandlerManager extends ExceptionHandlerManager
{
    /** @var string[] */
    public array $written = [];

    protected function write(string $message): void
    {
        $this->written[] = $message;
    }
}
