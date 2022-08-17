<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Console\Printer;

class Text
{
    public function __construct(
        public string            $text,
        public string|array|null $format = null,
    )
    {
    }
}
