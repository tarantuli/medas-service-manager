<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\Interfaces\CacheManager;

class PostInstallRunner
{
    /**
     * This method should only be called by "composer update" or similar command line scripts.
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
