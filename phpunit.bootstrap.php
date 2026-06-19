<?php

declare(strict_types=1);

use Medas\ObjectInstantiator\ObjectInstantiator;
use Medas\ServiceManager\{
    ErrorHandling\AggressiveErrorPolicy,
    ServiceConfig,
    ServiceManager,
    ServiceManagerPackage
};
use Medas\ObjectInstantiator\ObjectInstantiatorPackage;

chdir(__DIR__);

new ServiceManager(function (): ServiceConfig {
    $config = new ServiceConfig(ObjectInstantiator::class, new AggressiveErrorPolicy());

    $config->addPackages([
        ServiceManagerPackage::instance(),
        ObjectInstantiatorPackage::instance(),
    ]);

    return $config;
});
