<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\ServiceManager\AsSingleton;
use Medas\ServiceManager\ConfigOptions\ConfigGroup;
use Medas\ServiceManager\ConfigOptions\ConfigOption;

class MockConfigOption implements ConfigOption
{
    use AsSingleton;

    public function group(): ConfigGroup
    {
        return MockConfigGroup::instance();
    }

    public function name(): string
    {
        return 'project';
    }

    public function description(): string
    {
        return 'The project';
    }

    public function isValid(mixed $value): bool
    {
        return is_string($value);
    }

    public function hasDefault(): bool
    {
        return false;
    }

    public function default(): mixed
    {
        return null;
    }
}
