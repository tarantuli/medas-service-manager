<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Console\ConsolePackage;

class ServiceManagerPackage extends BasePackage
{
    use AsSingleton;

    public function dependencies(): array
    {
        return $this->dependenciesByClass([
            ConsolePackage::class,
        ]);
    }

    public function sourceDirectory(): string
    {
        return __DIR__;
    }

    public function postInstall(): void
    {
        sm()->primeCaches();
    }

    public function initialize(): void
    {
        require_once __DIR__ . '/GlobalFunctions.php';
        parent::initialize();
    }
}
