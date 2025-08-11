<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\{Attributes\Service, StringMaker};

#[Service]
readonly class ThrowableNormalizer
{
    public function __construct(
        private TraceNormalizer $traceNormalizer,
    )
    {
    }

    public function normalize(\Throwable $exception): array
    {
        return [
            'message' => StringMaker::instance()->forceUtf8($exception->getMessage()),
            'code' => $exception->getCode(),
            'fileName' => $exception->getFile(),
            'lineNumber' => $exception->getLine(),
            'trace' => $this->traceNormalizer->normalize($exception),
        ];
    }
}
