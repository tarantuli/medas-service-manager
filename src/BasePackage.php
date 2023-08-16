<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

abstract class BasePackage implements Package
{
    public function initialize(ServiceConfig $config): void
    {
        // Do nothing
    }

    public function postInstall(): void
    {
        // Do nothing
    }
}
