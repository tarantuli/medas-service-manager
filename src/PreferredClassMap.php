<?php

namespace Medas\ServiceContainer;

use Medas\ServiceContainer\Attributes\PreferredClass;

class PreferredClassMap
{
    private array $map = [];

    public function __construct(\ReflectionMethod $reflectionMethod)
    {
        foreach ($reflectionMethod->getAttributes(PreferredClass::class) as $preferredClass) {
            /** @var PreferredClass $preferredClassData */
            $preferredClassData = $preferredClass->newInstance();
            $this->map[$preferredClassData->type] = $preferredClassData->className;
        }
    }

    public function forType(string $type): ?string
    {
        return $this->map[$type] ?? null;
    }
}
