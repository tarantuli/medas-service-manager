<?php

declare(strict_types=1);

namespace Medas\ServiceManager\DataTree;

class History
{
    public function __construct(
        public string $source,
        public mixed  $value,
    )
    {
    }
}
