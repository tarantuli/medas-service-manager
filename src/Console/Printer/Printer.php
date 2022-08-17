<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Console\Printer;

interface Printer
{
    public function printLine(Text ...$texts): self;
}
