<?php

declare(strict_types=1);

use Medas\ConfigManager\ConfigManager;
use Medas\ServiceManager\ServiceManager;
use Medas\ServiceManager\ServiceManagerPackage;

$sm = ServiceManager::get();
$sm->addPackage(ServiceManagerPackage::instance());

/** @var $config ConfigManager */
$config = $sm->resolve(ConfigManager::class);
$config->readEnv(__DIR__);
$config->addDirectory(__DIR__ . '/tests/MockUps');
