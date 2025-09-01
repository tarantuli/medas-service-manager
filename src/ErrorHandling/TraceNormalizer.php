<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\{Attributes\Service, StringMaker};

#[Service]
readonly class TraceNormalizer
{
    public function normalize(\Throwable $exception): array
    {
        $paths = [];

        foreach ($exception->getTrace() as $trace) {
            $arguments = [];

            foreach ($trace['args'] ?? [] as $arg) {
                $type = get_debug_type($arg);

                if (class_exists($type) || !is_scalar($arg)) {
                    $arguments[] = $type;
                }
                else {
                    $arguments[] = StringMaker::instance()->forceUtf8((string) $arg);
                }
            }

            $paths[] = [
                'file' => $trace['file'] ?? '[main]',
                'line' => $trace['line'] ?? 0,
                'function' => $trace['function'],
                'arguments' => $arguments,
            ];
        }

        return $paths;
    }
}
