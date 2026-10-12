<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\{Exceptions\TraceFormatter, Interfaces\ExceptionHandler};
use Medas\ServiceManager\{ServiceConfig, ServiceManager};

class ExceptionHandlerManager
{
    private ServiceManager|null $serviceManager = null;

    /** @var string[] */
    private array $handlerClasses = [];

    /** @var ExceptionHandler[]|null */
    private array|null $resolvedHandlers = null;

    public function __construct()
    {
        // This service is *not* instantiated automatically,
        // so don't add more dependencies, expecting them to be injected.
        set_exception_handler($this->handle(...));
    }

    // The handlers are only resolved when the first exception arrives: together with their
    // own dependencies (loggers, mailers, their config options) they are dozens of services,
    // and a request that throws nothing needs none of them.
    public function prepareHandlers(ServiceManager $serviceManager, ServiceConfig $config): void
    {
        $this->serviceManager = $serviceManager;
        $this->handlerClasses = $config->exceptionHandlers;
    }

    public function handle(\Throwable $exception): void
    {
        $handled = false;

        try {
            $handlers = $this->handlers();
        }
        catch (\Throwable) {
            // A handler that can't be built must not hide the exception it was meant to
            // handle: that one is still printed below, as an unhandled exception.
            $handlers = [];
        }

        foreach ($handlers as $handler) {
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

    /** @return ExceptionHandler[] */
    private function handlers(): array
    {
        if ($this->resolvedHandlers === null) {
            $resolved = [];

            foreach ($this->handlerClasses as $class) {
                $resolved[] = $this->serviceManager->resolve($class);
            }

            $this->resolvedHandlers = $resolved;
        }

        return $this->resolvedHandlers;
    }
}
