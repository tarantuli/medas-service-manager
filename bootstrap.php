<?php

declare(strict_types=1);

use Medas\ServiceManager\{ServiceManager, ServiceManagerPackage};

chdir(__DIR__);

require_once 'vendor/autoload.php';

$sm = ServiceManager::get();
$sm->addPackage(ServiceManagerPackage::instance());
