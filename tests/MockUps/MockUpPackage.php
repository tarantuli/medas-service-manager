<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\Core\{AsSingleton, BasePackage, Interfaces\ServiceConfigBuilder};

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

    public function initialize(ServiceConfigBuilder $config): void
    {
        medas()->serviceManager()->bindImplementation(service(DefaultLogger::class), Logger::class);

        parent::initialize($config);
    }
}
