<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\Interfaces\{FileSystemCache, PrimesCache};

/**
 * Prepares the caches after a `composer install` or `composer update`: lets services fill their caches in advance,
 * and registers the cache directories that `DirectoriesToClear` empties on the next install or
 * `services:clear-caches`.
 *
 * @assumes The working directory is the project root. The registry path is relative to it.
 * @see \Medas\ServiceManager\Cache\DirectoriesToClear
 */
readonly class CachePrimer
{
    public function __construct(
        private ServiceManager     $serviceManager,
        private Cache\CacheManager $cacheManager,
        private string             $dirsToClearPath = 'var/dirs-to-clear',
    )
    {
    }

    /**
     * @sideEffect When a persistent cache is registered, resolves every service that implements `PrimesCache`, and
     *             calls `primeCache()` on it.
     * @sideEffect Writes a registration file for every file system cache that wants to be cleared on install, unless
     *             it's already registered.
     *
     * @throws \RuntimeException When the registry directory or a registration file can't be written.
     */
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
            if ($cache instanceof FileSystemCache && $cache->clearCacheOnInstall()) {
                // A relative path is no problem, because during bootstrap the working directory is set to the project root.
                $pathToDirectoryToClear = $this->dirsToClearPath
                    . '/'
                    . sha1($cache->baseDirectory());

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
        if (!file_exists($this->dirsToClearPath)
                && !mkdir($this->dirsToClearPath, 0755, recursive: true)
                && !is_dir($this->dirsToClearPath)) {
            throw new \RuntimeException('Could not create directory ' . $this->dirsToClearPath);
        }
    }
}
