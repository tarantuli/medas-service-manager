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
        $implementors = [];

        foreach (sm()->getServiceClassNames() as $className) {
            if (new \ReflectionClass($className)->isAbstract()) {
                continue;
            }

            if (!is_a($className, $interface, allow_string: true)) {
                continue;
            }

            $implementors[] = $className;
        }

        return $implementors;
    }
}
