<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

use Medas\Core\Attributes\Service;
use Medas\Core\GlobalRepository;

#[Service]
class ImplementorFinder
{
    /** @return object[] */
    public function find(string $interface): array
    {
        $serviceManager = GlobalRepository::serviceManager();
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
