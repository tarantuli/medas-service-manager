<?php

declare(strict_types=1);

// This file should be in the global namespace

use Medas\ServiceManager\Interfaces\EnvManager;
use Medas\ServiceManager\ServiceManager;

function env(string $path): mixed
{
    static $em;

    if (!isset($em)) {
        /** @var EnvManager $em */
        $em = sm()->resolve(EnvManager::class);
    }

    return $em->getValue($path);
}

function sm(): ServiceManager
{
    static $sm;

    if (!isset($sm)) {
        $sm = ServiceManager::get();
    }

    return $sm;

}
