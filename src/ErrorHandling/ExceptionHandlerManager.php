<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\Attributes\Service;
use Medas\ServiceManager\ServiceConfig;

#[Service]
readonly class ExceptionHandlerManager
{
    public function __construct(
        private ServiceConfig $config,
    )
    {
        // This service is *not* instantiated automatically,
        // so don't add more dependencies, expecting them to be injected.
        set_exception_handler($this->handle(...));
    }

    public function handle(\Throwable $exception): void
    {
        foreach ($this->config->exceptionHandlers() as $handler) {
            $handler->handleException($exception);
        }
    }
}
