<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\Attributes\Service;
use Medas\ServiceManager\ServiceConfig;

#[Service]
class ExceptionHandlerManager
{
    private ServiceConfig $config;

    public function __construct()
    {
        // This service is *not* instantiated automatically,
        // so don't add more dependencies, expecting them to be injected.
        $serviceManager = medas()->serviceManager();

        $serviceManager->bindImplementation($this, ExceptionHandlerManager::class);

        $this->config = $serviceManager->config();

        set_exception_handler($this->handle(...));
    }

    public function handle(\Throwable $exception): void
    {
        foreach ($this->config->exceptionHandlers() as $handler) {
            $handler->handle($exception);
        }
    }
}
