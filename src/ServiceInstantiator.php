<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\Exceptions\CircularDependencyException;

#[Service]
class ServiceInstantiator
{
    private MethodArgumentsValueResolver $methodArgumentsValueFinder;
    /** @var string[] */
    private array $instantiating = [];

    public function __construct(ServiceManager $manager)
    {
        $this->methodArgumentsValueFinder = new MethodArgumentsValueResolver($manager);
        $manager->bindService($this, ServiceInstantiator::class);
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

        return $this->methodArgumentsValueFinder->resolve($constructor);
    }
}
