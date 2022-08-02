<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\ServiceManager\Attributes\Service;

#[Service]
class ErrorHandler
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

        ini_set('display_errors', false);
        error_reporting(E_ALL);
    }

    private function handleFatalErrors(): void
    {
        $error = error_get_last();

        if ($error["type"] == E_ERROR) {
            throw new \ErrorException($error['message'], $error['type'], $error['type'], $error['file'], $error['line']);
        }
    }
}
