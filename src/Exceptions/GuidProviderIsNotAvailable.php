<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Exceptions;

use Medas\Core\Exceptions\BaseException;

class GuidProviderIsNotAvailable extends BaseException
{
    public function pattern(): string
    {
        return 'no GuidProvider is provided, but it is needed. Try for instance morphp/medas-ramsey-uuid-bridge';
    }
}
