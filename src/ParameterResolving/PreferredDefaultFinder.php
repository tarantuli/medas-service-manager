<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ParameterResolving;

use Medas\ServiceManager\Attributes\PreferredDefault;
use Medas\ServiceManager\Attributes\Service;

#[Service]
class PreferredDefaultFinder implements ParameterResolver
{
    private object $result;

    public function priority(): int
    {
        return -190;
    }

    public function __serialize(): array
    {
        // This is needed to make sure $result isn't serialized
        return [];
    }

    public function __unserialize(array $data): void
    {
        // Do nothing
    }

    public function handle(\ReflectionParameter|\ReflectionProperty $parameter): bool
    {
        $preferredDefault = attribute(PreferredDefault::class, $parameter);

        if ($preferredDefault) {
            $this->result = service($preferredDefault->className);
            return true;
        }

        return false;
    }

    public function result(): object
    {
        return $this->result;
    }
}
