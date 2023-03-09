<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Attributes;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
class PreferredDefault
{
    public function __construct(
        public readonly string $className,
    )
    {
    }
}
