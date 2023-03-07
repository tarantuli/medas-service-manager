<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class PreferredClass
{
    public function __construct(
        public readonly string $type,
        public readonly string $className,
    )
    {
    }
}
