<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\Interfaces\ErrorPolicy;

/**
 * Shared scaffolding for error policy setters. Subclasses define which PHP error severities
 * should be promoted to exceptions by implementing {@see shouldThrow()}.
 */
abstract readonly class BaseErrorPolicy implements ErrorPolicy
{
    /**
     * Returns true when the given PHP error severity should be promoted to an exception.
     */
    abstract protected function shouldThrow(int $severity): bool;

    /** The severities that stop the script before an error handler runs, so only a shutdown function sees them */
    private const array FATAL_SEVERITIES = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_COMPILE_ERROR,
    ];

    /**
     * Installs this policy as PHP's error handler. Errors suppressed with `@` are left to PHP, because code that
     * suppresses an error deals with the outcome itself.
     *
     * @sideEffect Replaces the current error handler, and sets `error_reporting()` to `E_ALL`.
     * @sideEffect Registers a shutdown function that writes a fatal error (`E_ERROR`, `E_PARSE`, `E_CORE_ERROR` or
     *             `E_COMPILE_ERROR`) to `STDERR`, or to the error log when there's no `STDERR`.
     */
    public function set(): void
    {
        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            // An error suppressed with @ (error_reporting() then excludes its severity) is left alone, as PHP's own
            // handler does: code that suppresses an error deals with the outcome itself, e.g. a false return value
            if (!(error_reporting() & $severity)) {
                return false;
            }

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

        if ($error !== null && in_array($error['type'], self::FATAL_SEVERITIES, true)) {
            $message = sprintf(
                "[fatal error] %s in %s on line %d",
                $error['message'],
                $error['file'],
                $error['line']
            );

            defined('STDERR') ? fwrite(STDERR, $message) : error_log($message);
        }
    }
}
