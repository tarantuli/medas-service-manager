<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\Core\{Attributes\Service, Interfaces\ExceptionHandler};

#[Service]
class RecordingExceptionHandler implements ExceptionHandler
{
    /** @var \Throwable[] */
    public array $received = [];

    public function handleException(\Throwable $exception): bool
    {
        $this->received[] = $exception;

        return true;
    }
}
