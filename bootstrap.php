<?php

declare(strict_types=1);

use Medas\ServiceManager\{ServiceConfig, ServiceManager, ServiceManagerPackage};

chdir(__DIR__);

require_once 'vendor/autoload.php';

new ServiceManager(
    initializer: function (): ServiceConfig {
        $config = new ServiceConfig();
        $config->addPackage(ServiceManagerPackage::instance());

        return $config;
    }
);
