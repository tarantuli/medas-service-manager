<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\Exceptions\CircularDependencyException;
use Medas\ServiceManager\ParameterResolver\ParameterResolveManager;

#[Service]
class ServiceInstantiator
{
    private ParameterResolveManager $parameterResolveManager;

    /** @var string[] */
    private array $instantiating = [];

    public function __construct(ServiceManager $manager)
    {
        $this->parameterResolveManager = new ParameterResolveManager($manager);
        $manager->bindService($this, ParameterResolveManager::class);
    }

    public function instantiate(string $className): object
    {
        $this->checkCircularDependencies($className);
        $arguments = $this->getConstructorArgumentValues($className);
        unset($this->instantiating[$className]);

        return new $className(... $arguments);
    }

    private function checkCircularDependencies(string $className): void
    {
        if (isset($this->instantiating[$className])) {
            throw new CircularDependencyException(array_keys($this->instantiating), $className);
        }

        $this->instantiating[$className] = true;
    }

    private function getConstructorArgumentValues(string $className): array
    {
        $class = new \ReflectionClass($className);

        if (!$constructor = $class->getConstructor()) {
            return [];
        }

        return $this->parameterResolveManager->resolveMethod($constructor);
    }
}
