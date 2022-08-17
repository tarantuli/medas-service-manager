<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Cache;

use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\Exceptions\CacheNotFoundByNameException;

#[Service]
class CacheManager
{
    /** @var Interfaces\Cache[] */
    private array $caches = [];

    public function get(string $name = 'default'): Interfaces\Cache
    {
        if ($name === 'default' && !array_key_exists($name, $this->caches)) {
            $this->register(new NoopCache());
        }

        if (!array_key_exists($name, $this->caches)) {
            throw new CacheNotFoundByNameException($name);
        }

        return $this->caches[$name];
    }

    public function register(Interfaces\Cache $cache, string $name = 'default'): void
    {
        if (array_key_exists($name, $this->caches)) {
            unset($this->caches[$name]);
        }

        $this->caches[$name] = $cache;
    }

    public function clearAll(): void
    {
        foreach ($this->caches as $cache) {
            if ($cache instanceof Interfaces\Clearable) {
                $cache->clear();
            }
        }
    }
}
