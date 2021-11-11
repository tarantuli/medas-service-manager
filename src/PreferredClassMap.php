<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\ServiceManager\Attributes\PreferredClass;

class PreferredClassMap
{
    private array $map = [];

    public function __construct(\ReflectionMethod $method)
    {
        foreach ($method->getAttributes(PreferredClass::class) as $attribute) {
            /** @var PreferredClass $preferredClass */
            $preferredClass = $attribute->newInstance();
            $this->map[$preferredClass->type] = $preferredClass->className;
        }
    }

    public function forType(string $type): ?string
    {
        return $this->map[$type] ?? null;
    }
}
