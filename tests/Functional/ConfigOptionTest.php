<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Functional;

use Medas\ServiceManager\ConfigOptions\OptionController;
use Medas\ServiceManager\Interfaces\ConfigManager;
use Medas\ServiceManagerTest\MockUps\MockConfigOption;
use PHPUnit\Framework\TestCase;

class ConfigOptionTest extends TestCase
{
    public function testOptionPath(): void
    {
        $option = MockConfigOption::instance();
        $controller = service(OptionController::class);
        $manager = service(ConfigManager::class);

        $value = $manager->getValue($controller->getPath($option));

        self::assertEquals('service-manager', $value);
    }

    public function testOptionValidator(): void
    {
        $option = MockConfigOption::instance();

        self::assertNotTrue($option->isValid(false));
        self::assertTrue($option->isValid('string'));
    }
}
