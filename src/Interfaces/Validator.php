<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Interfaces;

interface Validator
{
    public function isValid(mixed $value): bool;
}
