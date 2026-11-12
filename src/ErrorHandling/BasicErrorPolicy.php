<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

use Medas\Core\Attributes\Service;

/**
 * Error policy for production: leaves every error to PHP and turns none of them into an exception. It still sets
 * `error_reporting()` to `E_ALL` and reports fatal errors at shutdown, like every error policy.
 *
 * It's the policy `ServiceConfigBuilder` uses when none is given.
 *
 * @assumes It's instantiated with `new` during bootstrap, so it can't have constructor dependencies.
 * @notFor Development; `AggressiveErrorPolicy` makes warnings and notices fail loudly there.
 * @see AggressiveErrorPolicy
 */
#[Service]
readonly class BasicErrorPolicy extends BaseErrorPolicy
{
    /**
     * Fatal errors can't be thrown as exceptions: PHP stops the script before an error handler runs. The only
     * fatal severity that does reach the handler is `E_USER_ERROR`, which PHP 8.4 deprecated.
     */
    protected function shouldThrow(int $severity): bool
    {
        return false;
    }
}
