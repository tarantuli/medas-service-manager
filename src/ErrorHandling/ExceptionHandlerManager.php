<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\{Exceptions\TraceFormatter, Interfaces\ExceptionHandler};
use Medas\ServiceManager\{ServiceConfig, ServiceManager};

/**
 * Installs the global exception handler, and passes every uncaught exception to the configured `ExceptionHandler`s.
 * Every handler gets every exception. When none of them handles it, the exception is written with its trace to
 * `STDERR`, or to the error log when there's no `STDERR`.
 *
 * A handler that throws counts as not having handled the exception: the other handlers still get it, and the
 * handler's own exception is reported too, so a broken logger or mailer can't hide the error it was meant to report.
 *
 * @assumes `ServiceManager` creates it with `new` during bootstrap, before the object instantiator exists, so it
 *          can't have injected dependencies.
 * @sideEffect The constructor replaces the global exception handler.
 * @state Resolves the handlers once, when the first exception arrives, and keeps them.
 */
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

    /**
     * Sets the handlers to use. They're only resolved when the first exception arrives: together with their own
     * dependencies, like loggers, mailers and their config options, they're dozens of services, and a request that
     * throws nothing needs none of them.
     */
    public function prepareHandlers(ServiceManager $serviceManager, ServiceConfig $config): void
    {
        $this->serviceManager = $serviceManager;
        $this->handlerClasses = $config->exceptionHandlers;
    }

    /**
     * When a handler can't be resolved, the exception is reported as unhandled, so it doesn't get lost.
     */
    public function handle(\Throwable $exception): void
    {
        $handled = false;

        try {
            $handlers = $this->handlers();
        }
        catch (\Throwable $failure) {
            // A handler that can't be built must not hide the exception it was meant to
            // handle: that one is still printed below, as an unhandled exception.
            $handlers = [];

            $this->write($this->describeFailure('[Exception handlers could not be resolved]', $failure));
        }

        foreach ($handlers as $handler) {
            try {
                if ($handler->handleException($exception)) {
                    // Don't short circuit if the exception was handled.
                    // Multiple handlers are allowed to handle the same exception.
                    $handled = true;
                }
            }
            catch (\Throwable $failure) {
                // The other handlers still get the exception, and if none of them handles
                // it, it's printed below as unhandled
                $this->write($this->describeFailure('[Exception handler failed] ' . $handler::class, $failure));
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

            $this->write($message);
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

    private function describeFailure(string $prefix, \Throwable $failure): string
    {
        return sprintf(
            "%s: %s in %s on line %d — %s\n\n",
            $prefix,
            $failure::class,
            $failure->getFile(),
            $failure->getLine(),
            $failure->getMessage()
        );
    }

    /**
     * Writes a report to `STDERR`, or to the error log when there's no `STDERR`. It's protected so tests can capture
     * the reports.
     */
    protected function write(string $message): void
    {
        defined('STDERR') ? fwrite(STDERR, $message) : error_log($message);
    }
}
