<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\ServiceManager\AsSingleton;
use Medas\ServiceManager\BasePackage;
use Medas\ServiceManager\ServiceConfig;

class MockUpPackage extends BasePackage
{
    use AsSingleton;

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
        sm()->bindService(service(DefaultLogger::class), Logger::class);

        parent::initialize($config);
    }
}
