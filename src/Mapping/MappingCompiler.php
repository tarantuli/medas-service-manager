<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\Cache\CacheManager;
use Medas\ServiceManager\ParameterResolving\ParameterResolveManager;
use Medas\ServiceManager\Interfaces\Package;
use Medas\ServiceManager\ServiceInstantiator;
use Medas\ServiceManager\ServiceManager;

#[Service]
class MappingCompiler
{
    private ServiceFinder $serviceFinder;
    private ServiceMapping $mapping;

    public function __construct(CacheManager $cacheManager)
    {
        $this->mapping = new ServiceMapping();
        $this->serviceFinder = new ServiceFinder($cacheManager);
        $this->addDefaultMappings();
    }

    private function addDefaultMappings(): void
    {
        $defaultMappings = [
            ServiceManager::class,
            ServiceInstantiator::class,
            ParameterResolveManager::class,
            CacheManager::class,
        ];

        foreach ($defaultMappings as $defaultMapping) {
            $this->mapping->set($defaultMapping, $defaultMapping);
        }
    }

    public function addPackage(Package $package): void
    {
        $this->mapping->add($this->serviceFinder->find($package->sourceDirectory()));
    }

    public function get(): ServiceMapping
    {
        return $this->mapping;
    }
}
