<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\Attributes\Service;

/**
 * Throws an exception on every PHP error, regardless of severity. Suitable for development.
 */
#[Service]
readonly class AggressiveErrorPolicy extends BaseErrorPolicy
{
    protected function shouldThrow(int $severity): bool
    {
        return true;
    }
}
