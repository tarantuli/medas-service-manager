<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\{AsSingleton, BasePackage, CorePackage};

class ServiceManagerPackage extends BasePackage
{
    use AsSingleton;

    public function dependencies(): array
    {
        return [
            CorePackage::instance(),
        ];
    }

    public function sourceDirectory(): string
    {
        return __DIR__;
    }

    public function postInstall(): void
    {
        new CachePrimer(medas()->serviceManager(), service(Cache\CacheManager::class))->prime();
    }
}
