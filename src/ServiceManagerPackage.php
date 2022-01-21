<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\ConfigManager\ConfigManagerPackage;

class ServiceManagerPackage extends BasePackage
{
    use AsSingleton;

    public function dependencies(): array
    {
        return $this->dependenciesByClass([
            ConfigManagerPackage::class,
        ]);
    }

    public function sourceDirectory(): string
    {
        return __DIR__;
    }
}
