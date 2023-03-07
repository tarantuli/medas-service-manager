<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Interfaces;

interface Serializer
{
    public function serialize(mixed $value): mixed;

    public function unserialize(Type $type, mixed $value): mixed;
}
