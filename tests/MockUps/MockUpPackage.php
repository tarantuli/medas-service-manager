<?php

declare(strict_types=1);

namespace Medas\Test\MockUps;

use Medas\ServiceManager\BasePackage;

class MockUpPackage extends BasePackage
{
    public function dependencies(): array
    {
        return [];
    }

    public function sourceDirectory(): string
    {
        return __DIR__;
    }
}
