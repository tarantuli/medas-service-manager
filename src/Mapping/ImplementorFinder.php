<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

use Medas\Core\{Attributes\Service, Interfaces\ImplementorFinder as ImplementorFinderInterface};

#[Service]
class ImplementorFinder implements ImplementorFinderInterface
{
    /** @return class-string[] */
    public function find(string $interface): array
    {
        return sm()->config->mapping->getImplementors($interface);
    }
}
