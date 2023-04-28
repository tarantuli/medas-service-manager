<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\Core\AsSingleton;
use Medas\ServiceManager\{BasePackage, ServiceConfig};

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
        sm()->bindImplementation(service(DefaultLogger::class), Logger::class);

        parent::initialize($config);
    }
}
