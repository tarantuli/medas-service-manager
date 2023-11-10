<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

use Medas\Core\{Attributes\Service, Interfaces\ImplementorFinder as ImplementorFinderInterface};

#[Service]
class ImplementorFinder implements ImplementorFinderInterface
{
    /** @return object[] */
    public function find(string $interface): array
    {
        $serviceManager = medas()->serviceManager();
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
