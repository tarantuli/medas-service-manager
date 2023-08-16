<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\{AsSingleton, CorePackage};
use Medas\ObjectInstantiator\ObjectInstantiatorPackage;

class ServiceManagerPackage extends BasePackage
{
    use AsSingleton;

    public function dependencies(): array
    {
        return [
            CorePackage::instance(),
            ObjectInstantiatorPackage::instance(),
        ];
    }

    public function sourceDirectory(): string
    {
        return __DIR__;
    }

    public function postInstall(): void
    {
        medas()->serviceManager()->primeCaches();
    }
}
