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
    Interfaces\ServiceManager
};
use Medas\ServiceManager\{CachePrimer, Exceptions\CacheNotFoundByName};

/**
 * Registry of named caches. Packages fetch a cache by name instead of creating
 * their own, so the application decides where cached data is stored.
 *
 * Until the application registers a cache, `'default'` is a `NoopCache`, so
 * caching is effectively switched off.
 *
 * @assumes The `ServiceManager` creates this instance itself during bootstrap.
 *          It isn't autowired, so new constructor dependencies won't be injected.
 * @assumes The `'memory'` cache can only be created on demand when some package
 *          provides a `MemoryCache` implementation.
 * @sideEffect `get()` registers a `NoopCache` as `'default'`, or a `MemoryCache`
 *             as `'memory'`, the first time either is requested without having
 *             been registered.
 * @sideEffect `clearAll()` clears every registered cache that implements
 *             `Clearable`.
 * @state Keeps the registered caches for the lifetime of the process. Registering
 *        a cache under an existing name replaces the previous one.
 * @notFor Storing or reading values; use the `Cache` returned by `get()` for that.
 * @see CachePrimer
 */
#[Service]
class CacheManager implements CacheManagerInterface
{
    /** @var Cache[] */
    private array $caches = [];

    public function __construct(
        /** Finds the `MemoryCache` implementation when `'memory'` is first requested. */
        private readonly ServiceManager $serviceManager,
    )
    {
        // This service is *not* instantiated automatically,
        // so don't add more dependencies, expecting them to be injected.
    }

    /**
     * Returns the cache with the given name. If no name is given, the name `'default'` is used.
     *
     * If no cache with the name `'default'` exists, a `NoopCache` will be created. This cache does nothing, always
     * returning the value from the getter. If no cache with the name `'memory'` exists, a `MemoryCache` will be
     * created.
     *
     * @throws CacheNotFoundByName When no cache with that name is registered and none can be created.
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

            if ($className === null) {
                throw new CacheNotFoundByName('memory');
            }

            $this->register(new $className(), 'memory');
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
