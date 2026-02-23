<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\{Attributes\Service, Interfaces\ErrorHandler};

/**
 * This error handler is not very aggressive; it only throws exceptions for errors that are fatal. Useful for production.
 */
#[Service]
class BasicErrorHandler implements ErrorHandler
{
    public function set(): void
    {
        $this->setErrorHandling();

        register_shutdown_function($this->handleFatalErrors(...));
    }

    private function setErrorHandling(): void
    {
        set_error_handler(function ($severity, $message, $file, $line) {
            if (in_array($severity, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
                throw new \ErrorException($message, $severity, $severity, $file, $line);
            }
        });

        error_reporting(E_ALL);
    }

    private function handleFatalErrors(): void
    {
        $error = error_get_last();

        if ($error !== null && $error["type"] === E_ERROR) {
            echo "[fatal error]\n";
        }
    }
}
