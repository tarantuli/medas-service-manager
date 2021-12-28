<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Interfaces;

interface Package
{
    /** @return Package[] */
    public function dependencies(): array;

    public function sourceDirectory(): string;
}
