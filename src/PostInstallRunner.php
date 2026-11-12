<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\Interfaces\CacheManager;

/**
 * Runs the post-install steps of every package.
 */
class PostInstallRunner
{
    /**
     * Only for `composer install`, `composer update` and similar command line scripts.
     *
     * @assumes The `ServiceManager` is initialized.
     * @sideEffect Clears every cache first, then calls `postInstall()` on every package.
     */
    public function run(): void
    {
        $serviceManager = medas()->serviceManager();

        $serviceManager->resolve(CacheManager::class)->clearAll();

        foreach ($serviceManager->config->packageClasses as $packageClass) {
            $packageClass::instance()->postInstall();
        }
    }
}
