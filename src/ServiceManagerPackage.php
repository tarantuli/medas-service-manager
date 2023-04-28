<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\{AsSingleton, CorePackage, GlobalRepository};
use Medas\ServiceManager\ParameterResolving\PreferredDefaultFinder;

class ServiceManagerPackage extends BasePackage
{
    use AsSingleton;

    public function dependencies(): array
    {
        return $this->dependenciesByClass([
            CorePackage::class,
        ]);
    }

    public function sourceDirectory(): string
    {
        return __DIR__;
    }

    public function postInstall(): void
    {
        GlobalRepository::serviceManager()->primeCaches();
    }

    public function initialize(ServiceConfig $config): void
    {
        parent::initialize($config);

        $config->addParameterResolver(
            service(PreferredDefaultFinder::class)
        );
    }
}
