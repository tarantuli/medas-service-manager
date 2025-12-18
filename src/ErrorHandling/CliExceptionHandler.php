<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\{Attributes\Service, StringMaker};

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

    public function printThrowable(\Throwable $exception): void
    {
        foreach (array_reverse($exception->getTrace()) as $trace) {
            if (isset($trace['file'])) {
                printf("%s:%u\n", $trace['file'], $trace['line']);
            }
            else {
                echo "[main]\n";
            }

            if (isset($trace['class'])) {
                printf("  %s::%s()\n", $trace['class'], $trace['function']);

                try {
                    $parameters = new \ReflectionMethod($trace['class'], $trace['function'])->getParameters();
                }
                catch (\ReflectionException) {
                    $parameters = null;
                }
            }
            else {
                printf("  %s()\n", $trace['function']);

                $parameters = null;
            }

            foreach ($trace['args'] ?? [] as $index => $argument) {
                printf(
                    "    %s: ",
                    $parameters && array_key_exists($index, $parameters)
                        ? $parameters[$index]->name
                        : $index
                );

                if (is_array($argument)) {
                    try {
                        $argument = json_encode($argument);
                    }
                    catch (\Exception) {
                        $argument = "array (... cannot be serialized ...)";
                    }
                }

                $type = get_debug_type($argument);

                if (class_exists($type)) {
                    printf("%s[%u]\n", $type, spl_object_id($argument));
                }
                elseif (!is_scalar($argument)) {
                    printf("%s\n", $type);
                }
                elseif (is_string($argument) && mb_detect_encoding($argument, 'UTF-8')) {
                    printf("%s\n", mb_substr($argument, 0, 156));
                }
                else {
                    printf("%s\n", StringMaker::instance()->forceUtf8((string) $argument));
                }
            }

            printf("\n");
        }

        printf(
            "\n%s:%u [%u]\n%s\n\n",
            $exception->getFile(),
            $exception->getLine(),
            $exception->getCode(),
            $exception->getMessage()
        );
    }
}
