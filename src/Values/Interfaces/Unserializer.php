<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Values\Interfaces;

interface Unserializer
{
    public function unserialize(string $value): mixed;
}
