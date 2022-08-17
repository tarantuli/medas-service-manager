<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Values\Interfaces;

interface Serializer
{
    public function serialize(mixed $value): string;
}
