<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\{Attributes\Service, Interfaces\ErrorHandler};

/**
 * This error handler is very aggressive: it will throw an exception on every error. Useful during development.
 */
#[Service]
class AggressiveErrorHandler implements ErrorHandler
{
    public function set(): void
    {
        $this->setErrorHandling();

        register_shutdown_function($this->handleFatalErrors(...));
    }

    private function setErrorHandling(): void
    {
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, $severity, $severity, $file, $line);
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
