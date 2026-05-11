<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\Interfaces\ExceptionHandler;
use Medas\ServiceManager\ServiceConfig;

class ExceptionHandlerManager
{
    private readonly ServiceConfig $config;

    /** @var ExceptionHandler[]|null */
    private array|null $resolvedHandlers = null;

    public function __construct(ServiceConfig $config)
    {
        $this->config = $config;

        // This service is *not* instantiated automatically,
        // so don't add more dependencies, expecting them to be injected.
        set_exception_handler($this->handle(...));
    }

    public function handle(\Throwable $exception): void
    {
        foreach ($this->resolvedHandlers ?? $this->resolveHandlers() as $handler) {
            $handler->handleException($exception);
        }
    }

    private function resolveHandlers(): array
    {
        $this->resolvedHandlers = $this->config->exceptionHandlers();

        foreach ($this->config->exceptionHandlerClasses() as $class) {
            try {
                $this->resolvedHandlers[] = service($class);
            }
            catch (\Throwable) {
                // Resolution failure must not suppress the original exception.
            }
        }

        return $this->resolvedHandlers;
    }
}
