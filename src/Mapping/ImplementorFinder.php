<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

use Medas\ServiceManager\Attributes\Service;

#[Service]
class ImplementorFinder
{
    /** @return object[] */
    public function find(string $interface): array
    {
        $implementors = [];

        foreach (sm()->getServiceClassNames() as $className) {
            $class = new \ReflectionClass($className);

            if ($class->isAbstract()) {
                continue;
            }

            if (!$class->implementsInterface($interface)) {
                continue;
            }

            $implementors[] = sm()->resolve($className);
        }

        return $implementors;
    }
}
