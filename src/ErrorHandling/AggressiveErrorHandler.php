<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\Attributes\Service;

/**
 * Throws an exception on every PHP error, regardless of severity. Suitable for development.
 */
#[Service]
readonly class AggressiveErrorHandler extends BaseErrorHandler
{
    protected function shouldThrow(int $severity): bool
    {
        return true;
    }
}
