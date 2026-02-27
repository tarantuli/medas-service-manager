<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\Core\{AsSingleton, BasePackage, Interfaces\ServiceConfig};

class MockUpPackage extends BasePackage
{
    use AsSingleton;

    public function isTestPackage(): bool
    {
        return true;
    }

    public function dependencies(): array
    {
        return [];
    }

    public function sourceDirectory(): string
    {
        return __DIR__;
    }

    public function initialize(ServiceConfig $config): void
    {
        medas()->serviceManager()->bindImplementation(service(DefaultLogger::class), Logger::class);

        parent::initialize($config);
    }
}
