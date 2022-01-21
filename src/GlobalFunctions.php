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

/**
 * The return value  is an object of type $type. This is specified in PhpStorm in .phpstorm.meta.php
 */
function service(string $type): object
{
    return sm()->resolve($type);
}

function getPropertyValue(object $object, string $propertyName): mixed
{
    $class = new ReflectionClass($object);
    $property = $class->getProperty($propertyName);
    $property->setAccessible(true);

    return $property->getValue($object);
}
