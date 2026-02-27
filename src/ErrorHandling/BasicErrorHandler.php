<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\Attributes\Service;

/**
 * Throws exceptions only for fatal error severities. Suitable for production.
 */
#[Service]
readonly class BasicErrorHandler extends BaseErrorHandler
{
    protected function shouldThrow(int $severity): bool
    {
        return in_array(
            $severity,
            [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR],
            true
        );
    }
}
