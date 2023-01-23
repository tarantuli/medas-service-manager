<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Exceptions;

use Medas\Core\Exceptions\BaseException;

class IndexNotFoundInDataTree extends BaseException
{

    public function __construct(string $index)
    {
        parent::__construct($index);
    }

    public function pattern(): string
    {
        return 'no value found for index %s';
    }
}
