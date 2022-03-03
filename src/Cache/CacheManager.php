<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Cache;

use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\Exceptions\CacheNameAlreadyUsedException;
use Medas\ServiceManager\Exceptions\CacheNotFoundByNameException;
use Medas\ServiceManager\Interfaces\{Cache, Clearable};
use Medas\ServiceManager\ServiceManager;

#[Service]
class CacheManager
{
    /** @var Cache[] */
    private array $caches = [];

    public function __construct(ServiceManager $manager)
    {
        $manager->bindService($this, CacheManager::class);
    }

    public function get(string $name = 'default'): Cache
    {
        if ($name === 'default' && !array_key_exists($name, $this->caches)) {
            $this->register(new NoopCache());
        }

        if (!array_key_exists($name, $this->caches)) {
            throw new CacheNotFoundByNameException($name);
        }

        return $this->caches[$name];
    }

    public function register(Cache $cache, string $name = 'default'): void
    {
        if (array_key_exists($name, $this->caches)) {
            throw new CacheNameAlreadyUsedException($name);
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
}
