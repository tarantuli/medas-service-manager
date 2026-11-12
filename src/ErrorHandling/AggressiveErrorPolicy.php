<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\Attributes\Service;

/**
 * Error policy for development: turns every PHP error that reaches the error handler into an `ErrorException`,
 * whatever its severity, so warnings, notices and deprecations fail loudly.
 *
 * Errors suppressed with `@` are still left alone, and fatal errors stop the script before any handler runs.
 *
 * @assumes It's instantiated with `new` during bootstrap, so it can't have constructor dependencies.
 * @notFor Production, where a deprecation in a dependency would break requests; use `BasicErrorPolicy` there.
 * @see BasicErrorPolicy
 */
#[Service]
readonly class AggressiveErrorPolicy extends BaseErrorPolicy
{
    protected function shouldThrow(int $severity): bool
    {
        return true;
    }
}
