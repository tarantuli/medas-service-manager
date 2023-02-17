<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\ServiceManager;

#[Service]
class ImplementorFinder
{
    /** @return object[] */
    public function find(string $interface): array
    {
        $serviceManager = ServiceManager::get();
        $implementors = [];

        foreach ($serviceManager->getServiceClassNames() as $className) {
            $class = new \ReflectionClass($className);

            if ($class->isAbstract()) {
                continue;
            }

            if (!$class->implementsInterface($interface)) {
                continue;
            }

            $implementors[] = $serviceManager->resolve($className);
        }

        return $implementors;
    }
}
