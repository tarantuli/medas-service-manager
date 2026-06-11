<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\Interfaces\ErrorHandler;

/**
 * Shared scaffolding for error handlers. Subclasses define which PHP error severities
 * should be promoted to exceptions by implementing {@see shouldThrow()}.
 */
abstract readonly class BaseErrorHandler implements ErrorHandler
{
    /**
     * Returns true when the given PHP error severity should be promoted to an exception.
     */
    abstract protected function shouldThrow(int $severity): bool;

    public function set(): void
    {
        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if ($this->shouldThrow($severity)) {
                throw new \ErrorException($message, $severity, $severity, $file, $line);
            }

            return false;
        });

        error_reporting(E_ALL);

        register_shutdown_function($this->handleFatalErrors(...));
    }

    private function handleFatalErrors(): void
    {
        $error = error_get_last();

        if ($error !== null && $error['type'] === E_ERROR) {
            $message = sprintf(
                "[fatal error] %s in %s on line %d\n",
                $error['message'],
                $error['file'],
                $error['line'],
            );

            defined('STDERR') ? fwrite(STDERR, $message) : error_log($message);
        }
    }
}
