<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Exceptions;

use Medas\Core\Exceptions\BaseException;

class ClassDependsOnUnknownClassException extends BaseException
{
    public function __construct(string $sourceClass, string $dependsOn)
    {
        parent::__construct($sourceClass, $dependsOn);
    }

    public function pattern(): string
    {
        return 'class %s depends on unknown class %s';
    }
}
