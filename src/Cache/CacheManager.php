<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Cache;

use Medas\Core\{
    Attributes\Service,
    Caching\NoopCache,
    Interfaces\Cache,
    Interfaces\CacheManager as CacheManagerInterface,
    Interfaces\Clearable,
    Interfaces\MemoryCache,
    Interfaces\NonPersistentCache,
    Interfaces\ServiceManager,
    Serializers\NoopSerializer
};
use Medas\ServiceManager\Exceptions\CacheNotFoundByName;

#[Service]
class CacheManager implements CacheManagerInterface
{
    /** @var Cache[] */
    private array $caches = [];

    public function __construct(
        private readonly ServiceManager $serviceManager,
    )
    {
        // This service is *not* instantiated automatically,
        // so don't add more dependencies, expecting them to be injected.
    }

    /**
     * Returns the cache with the given name. If no name is given, the name "default" is used.
     *
     * If no cache with the name "default" exists, a NoopCache will be created. This cache does nothing, always
     * returning the value from the setter. If no cache with the name "memory" exists, a MemoryCache will be created.
     */
    public function get(string|null $name = null): Cache
    {
        if ($name === null) {
            $name = 'default';
        }

        if ($name === 'default' && !array_key_exists($name, $this->caches)) {
            $this->register(new NoopCache());
        }

        if ($name === 'memory' && !array_key_exists($name, $this->caches)) {
            $className = $this->serviceManager->findImplementingClass(MemoryCache::class);
            $memoryCache = new $className(new NoopSerializer());

            $this->register($memoryCache, 'memory');
        }

        if (!array_key_exists($name, $this->caches)) {
            throw new CacheNotFoundByName($name);
        }

        return $this->caches[$name];
    }

    public function getAll(): array
    {
        return $this->caches;
    }

    public function register(Cache $cache, string $name = 'default'): void
    {
        if (array_key_exists($name, $this->caches)) {
            unset($this->caches[$name]);
        }

        $this->caches[$name] = $cache;
    }

    public function clearAll(): void
    {
        foreach ($this->caches as $cache) {
            if ($cache instanceof Clearable) {
                $cache->clear();
            }
        }
    }

    public function hasPersistentCache(): bool
    {
        return array_any($this->caches, fn($cache) => !$cache instanceof NonPersistentCache);
    }
}
