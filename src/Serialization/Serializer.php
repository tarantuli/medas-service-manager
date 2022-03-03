<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Serialization;

class Serializer implements \Medas\ServiceManager\Interfaces\Serializer
{
    public function serialize(mixed $value): string
    {
        return serialize($value);
    }

    public function unserialize(string $value): mixed
    {
        return unserialize($value);
    }
}
