<?php

declare(strict_types=1);

use Medas\ConfigManager\ConfigManager;
use Medas\ServiceManager\ServiceManager;

$sm = ServiceManager::get();
$sm->addPackage(ServiceManager::class);
$sm->addPackage(ConfigManager::class);

/** @var $config ConfigManager */
$config = $sm->resolve(ConfigManager::class);
$config->readEnv(__DIR__);
$config->addDirectory(__DIR__ . '/tests/MockUps');
