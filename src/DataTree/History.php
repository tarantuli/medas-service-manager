<?php

declare(strict_types=1);

namespace Medas\ServiceManager\DataTree;

/**
 * One write to a value in a `DataTree`: where it came from, and the value it set.
 */
readonly class History
{
    public function __construct(
        public string $source,
        public mixed  $value,
    )
    {
    }
}
