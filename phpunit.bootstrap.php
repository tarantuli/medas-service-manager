<?php

declare(strict_types=1);

use Medas\ServiceManager\{
    ErrorHandling\AggressiveErrorHandler,
    ServiceConfig,
    ServiceManager,
    ServiceManagerPackage
};

chdir(__DIR__);

new ServiceManager(function (): ServiceConfig {
    $config = new ServiceConfig(new AggressiveErrorHandler());

    $config->addPackages([
        ServiceManagerPackage::instance(),
    ]);

    return $config;
});
