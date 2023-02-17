<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

class ServiceManagerPackage extends BasePackage
{
    use AsSingleton;

    public function dependencies(): array
    {
        return $this->dependenciesByClass([
        ]);
    }

    public function sourceDirectory(): string
    {
        return __DIR__;
    }

    public function postInstall(): void
    {
        ServiceManager::get()->primeCaches();
    }

    public function initialize(ServiceConfig $config): void
    {
        require_once __DIR__ . '/GlobalFunctions.php';
        parent::initialize($config);
    }
}
