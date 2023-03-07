<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Interfaces;

interface StringSerializer extends Serializer
{
    public function serialize(mixed $value): string;

    /** @param string $value */
    public function unserialize(Type $type, mixed $value): mixed;
}
