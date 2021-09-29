<?php

declare(strict_types=1);

namespace Medas\ServiceContainer\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class PreferredClass
{
    public function __construct(public string $type, public string $className)
    {
    }
}
