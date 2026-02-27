<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\Interfaces\{FileSystemCache, PrimesCache};

readonly class CachePrimer
{
    public function __construct(
        private ServiceManager     $serviceManager,
        private Cache\CacheManager $cacheManager,
    )
    {
    }

    public function prime(): void
    {
        if ($this->cacheManager->hasPersistentCache()) {
            $this->checkServices();
        }

        $this->registerCacheDirectories();
    }

    private function checkServices(): void
    {
        foreach ($this->serviceManager->getServiceClassNames() as $serviceName) {
            if (new \ReflectionClass($serviceName)->implementsInterface(PrimesCache::class)) {
                /** @var PrimesCache $service */
                $service = $this->serviceManager->resolve($serviceName);

                $service->primeCache();
            }
        }
    }

    private function registerCacheDirectories(): void
    {
        $ensuredPath = false;

        foreach ($this->cacheManager->getAll() as $cache) {
            if ($cache instanceof FileSystemCache) {
                // A relative path is no problem, because during bootstrap the working directory is set to the project root.
                $pathToDirectoryToClear = 'var/dirs-to-clear/' . sha1($cache->baseDirectory());

                if (!file_exists($pathToDirectoryToClear)) {
                    if (!$ensuredPath) {
                        $this->ensurePath();

                        $ensuredPath = true;
                    }

                    if (file_put_contents($pathToDirectoryToClear, $cache->baseDirectory() . "\n") === false) {
                        throw new \RuntimeException("Could not write to $pathToDirectoryToClear");
                    }
                }
            }
        }
    }

    private function ensurePath(): void
    {
        if (!file_exists('var/dirs-to-clear')
                && !mkdir('var/dirs-to-clear', 0755, recursive: true)
                && !is_dir('var/dirs-to-clear')) {
            throw new \RuntimeException('Could not create directory var/dirs-to-clear');
        }
    }
}
