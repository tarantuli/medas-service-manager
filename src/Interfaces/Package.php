<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Interfaces;

interface Package extends IsSingleton
{
    /** @return Package[] */
    public function dependencies(): array;

    public function sourceDirectory(): string;

    public function initialize(): void;

    public function postInstall(): void;
}
