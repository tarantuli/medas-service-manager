<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\Interfaces\IsSingleton;

interface Package extends IsSingleton
{
    /** @return Package[] */
    public function dependencies(): array;

    public function sourceDirectory(): string;

    public function initialize(ServiceConfig $config): void;

    public function postInstall(): void;
}
