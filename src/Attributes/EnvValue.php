<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Attributes;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class EnvValue
{
    public function __construct(public string $path)
    {
    }
}
