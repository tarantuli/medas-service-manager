<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\Attributes\Service;

#[Service]
readonly class CliExceptionHandler implements ExceptionHandler
{
    public static function create(): static
    {
        return new static(new ThrowableNormalizer(new TraceNormalizer()));
    }

    public function __construct(
        private ThrowableNormalizer $throwableNormalizer,
    )
    {
    }

    public function handleException(\Throwable $exception): void
    {
        if (PHP_SAPI === 'cli') {
            echo json_encode($this->throwableNormalizer->normalize($exception), JSON_PRETTY_PRINT), "\n\n";
        }
    }
}
