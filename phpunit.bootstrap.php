<?php

declare(strict_types=1);

use Medas\ServiceManager\{ServiceConfig, ServiceManager, ServiceManagerPackage};

chdir(__DIR__);

new ServiceManager(function (): ServiceConfig {
    $config = new ServiceConfig();

    $config->addPackages([
        ServiceManagerPackage::instance(),
    ]);

    return $config;
});
