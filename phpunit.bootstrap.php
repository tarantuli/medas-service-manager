<?php

declare(strict_types=1);

use Medas\EnvManager\EnvManager;
use Medas\ServiceManager\ServiceManager;

$sm = ServiceManager::get();
$sm->addPackage(ServiceManager::class);
$sm->addPackage(EnvManager::class);

/** @var $env EnvManager */
$env = $sm->resolve(EnvManager::class);
$env->setRoot(__DIR__ . '/tests/MockRoot');
