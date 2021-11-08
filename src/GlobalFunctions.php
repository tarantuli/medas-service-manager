<?php

declare(strict_types=1);

// This file should be in the global namespace

use Medas\ServiceManager\Interfaces\ConfigManager;
use Medas\ServiceManager\ServiceManager;

function config(string $path): mixed
{
    static $config;

    if (!isset($config)) {
        /** @var ConfigManager $config */
        $config = sm()->resolve(ConfigManager::class);
    }

    return $config->getValue($path);
}

function sm(): ServiceManager
{
    static $sm;

    if (!isset($sm)) {
        $sm = ServiceManager::get();
    }

    return $sm;

}
