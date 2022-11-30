<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Values\Interfaces;

interface Guid extends \Stringable
{
    public function toBytes(): string;
}
