<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\{Exceptions\TraceFormatter, Interfaces\ExceptionHandler};
use Medas\ServiceManager\{ServiceConfig, ServiceManager};

class ExceptionHandlerManager
{
    /** @var ExceptionHandler[]|null */
    private array|null $resolvedHandlers = null;

    public function __construct()
    {
        // This service is *not* instantiated automatically,
        // so don't add more dependencies, expecting them to be injected.
        set_exception_handler($this->handle(...));
    }

    public function resolveHandlers(ServiceManager $serviceManager, ServiceConfig $config): void
    {
        $this->resolvedHandlers = [];

        foreach ($config->exceptionHandlers as $class) {
            $this->resolvedHandlers[] = $serviceManager->resolve($class);
        }
    }

    public function handle(\Throwable $exception): void
    {
        $handled = false;

        foreach ($this->resolvedHandlers as $handler) {
            if ($handler->handleException($exception)) {
                // Don't short circuit if the exception was handled.
                // Multiple handlers are allowed to handle the same exception.
                $handled = true;
            }
        }

        if (!$handled) {
            if (class_exists(TraceFormatter::class)) {
                $trace = new TraceFormatter(156)->toString($exception->getTrace());
            }
            else {
                $trace = $exception->getTraceAsString();
            }

            $message = sprintf(
                "[Unhandled exception] %s in %s on line %d — %s\n\n%s",
                $exception::class,
                $exception->getFile(),
                $exception->getLine(),
                $exception->getMessage(),
                $trace
            );

            defined('STDERR') ? fwrite(STDERR, $message) : error_log($message);
        }
    }
}
