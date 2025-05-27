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
    Serializers\NoopSerializer
};
use Medas\ServiceManager\Exceptions\CacheNotFoundByName;

#[Service]
class CacheManager implements CacheManagerInterface
{
    /** @var Cache[] */
    private array $caches = [];

    public function __construct()
    {
        // This service is *not* instantiated automatically,
        // so don't add more dependencies, expecting them to be injected.
    }

    public function get(string $name = 'default'): Cache
    {
        if ($name === 'default' && !array_key_exists($name, $this->caches)) {
            $this->register(new NoopCache());
        }

        if ($name === 'memory' && !array_key_exists($name, $this->caches)) {
            $className = sm()->findImplementingClass(MemoryCache::class);
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

    public function hasPersisentCache(): bool
    {
        foreach ($this->caches as $cache) {
            if (!$cache instanceof NonPersistentCache) {
                return true;
            }
        }

        return false;
    }
}
