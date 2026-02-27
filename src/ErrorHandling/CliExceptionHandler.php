<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\{Attributes\Service, Interfaces\ExceptionHandler, StringMaker};

#[Service]
readonly class CliExceptionHandler implements ExceptionHandler
{
    public function handleException(\Throwable $exception): void
    {
        if (PHP_SAPI !== 'cli') {
            return;
        }

        $this->printThrowable($exception);
    }

    private function printThrowable(\Throwable $exception): void
    {
        foreach (array_reverse($exception->getTrace()) as $trace) {
            if (isset($trace['file'])) {
                fprintf(STDERR, "%s:%u\n", $trace['file'], $trace['line']);
            }
            else {
                fprintf(STDERR, "[main]\n");
            }

            if (isset($trace['class'])) {
                fprintf(STDERR, "  %s::%s()\n", $trace['class'], $trace['function']);

                try {
                    $parameters = new \ReflectionMethod(
                        $trace['class'],
                        $trace['function']
                    )->getParameters();
                }
                catch (\ReflectionException) {
                    $parameters = null;
                }
            }
            else {
                fprintf(STDERR, "  %s()\n", $trace['function']);

                $parameters = null;
            }

            foreach ($trace['args'] ?? [] as $index => $argument) {
                fprintf(
                    STDERR,
                    "    %s: ",
                    $parameters && array_key_exists($index, $parameters)
                        ? $parameters[$index]->name
                        : $index
                );

                if (is_array($argument)) {
                    try {
                        $argument = json_encode($argument, JSON_THROW_ON_ERROR);
                    }
                    catch (\Exception) {
                        $argument = "array (... cannot be serialized ...)";
                    }
                }

                $type = get_debug_type($argument);

                if (class_exists($type)) {
                    fprintf(STDERR, "%s[%u]\n", $type, spl_object_id($argument));
                }
                elseif (!is_scalar($argument)) {
                    fprintf(STDERR, "%s\n", $type);
                }
                elseif (is_string($argument) && mb_detect_encoding($argument, 'UTF-8')) {
                    fprintf(STDERR, "%s\n", mb_substr($argument, 0, 156));
                }
                else {
                    fprintf(STDERR, "%s\n", StringMaker::instance()->forceUtf8((string) $argument));
                }
            }

            fprintf(STDERR, "\n");
        }

        fprintf(
            STDERR,
            "\n%s:%u [%s]\n%s\n\n",
            $exception->getFile(),
            $exception->getLine(),
            $exception->getCode(),
            $exception->getMessage()
        );
    }
}
