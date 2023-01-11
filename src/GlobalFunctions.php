<?php

declare(strict_types=1);

// This file should be in the global namespace

use Medas\ServiceManager\Interfaces\ConfigManager;
use Medas\ServiceManager\ServiceManager;

function config(string $path): mixed
{
    static $config;

    if (!isset($config)) {
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

/**
 * The return value  is an object of type $type. This is specified in PhpStorm in .phpstorm.meta.php
 */
function attribute(
    string                                                                                                    $type,
    ReflectionClassConstant|ReflectionClass|ReflectionFunctionAbstract|ReflectionParameter|ReflectionProperty $reflector
): ?object
{
    if (!$attributes = $reflector->getAttributes($type, ReflectionAttribute::IS_INSTANCEOF)) {
        return null;
    }

    return $attributes[0]->newInstance();
}

function getPropertyValue(object $object, string $propertyName): mixed
{
    return (new ReflectionClass($object))->getProperty($propertyName)->getValue($object);
}

/** @return \ReflectionNamedType[] */
function parameterTypes(\ReflectionParameter|\ReflectionProperty $parameter): array
{
    $type = $parameter->getType();

    if (!$type) return [];

    return $type instanceof \ReflectionUnionType
        ? $type->getTypes()
        : [$type];
}
