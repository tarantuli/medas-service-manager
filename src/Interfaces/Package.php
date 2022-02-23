<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Interfaces;

interface Package
{
    public static function instance(): self;

    /** @return Package[] */
    public function dependencies(): array;

    public function sourceDirectory(): string;

    public function initialize(): void;
}
