<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\Interfaces\FileSystemCache;
use Medas\Core\Interfaces\PrimesCache;
use Medas\ServiceManager\Cache\CacheManager;

readonly class CachePrimer
{
    public function __construct(
        private ServiceManager $serviceManager,
        private CacheManager   $cacheManager,
    )
    {
    }

    public function prime(): void
    {
        foreach ($this->serviceManager->getServiceClassNames() as $serviceName) {
            if ((new \ReflectionClass($serviceName))->implementsInterface(PrimesCache::class)) {
                /** @var PrimesCache $service */
                $service = $this->serviceManager->resolve($serviceName);
                $service->primeCache();
            }
        }

        $this->registerCacheDirectories();
    }

    private function registerCacheDirectories(): void
    {
        $ensuredPath = false;

        foreach ($this->cacheManager->getAll() as $cache) {
            if ($cache instanceof FileSystemCache) {
                $pathToDirectoryToClear = 'var/dirs-to-clear/' . sha1($cache->baseDirectory());

                if (!file_exists($pathToDirectoryToClear)) {
                    if (!$ensuredPath) {
                        $this->ensurePath();
                        $ensuredPath = true;
                    }

                    file_put_contents($pathToDirectoryToClear, $cache->baseDirectory() . "\n");
                }
            }
        }
    }

    private function ensurePath(): void
    {
        if (!file_exists('var')) {
            mkdir('var');
        }

        if (!file_exists('var/dirs-to-clear')) {
            mkdir('var/dirs-to-clear');
        }
    }
}
