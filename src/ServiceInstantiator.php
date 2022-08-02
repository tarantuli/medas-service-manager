<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\Exceptions\CircularDependencyException;
use Medas\ServiceManager\Exceptions\DebuggedCircularDependencyException;
use Medas\ServiceManager\ParameterResolver\ParameterResolveManager;
use Medas\ServiceManager\ParameterResolver\ParameterResolver;

#[Service]
class ServiceInstantiator
{
    private ParameterResolveManager $parameterResolveManager;

    /** @var string[] */
    private array $instantiating = [];

    public function __construct(ServiceManager $serviceManager)
    {
        $this->parameterResolveManager = new ParameterResolveManager($serviceManager);
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
        if (defined('DEBUG_CIRCULAR_DEPENDENCIES')) {
            $this->checkCircularDependenciesWithAdditionalDebugging($className);
        }
        else {
            if (isset($this->instantiating[$className])) {
                throw new CircularDependencyException(array_keys($this->instantiating), $className);
            }

            $this->instantiating[$className] = true;
        }
    }

    private function checkCircularDependenciesWithAdditionalDebugging(string $className): void
    {
        foreach (array_reverse(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)) as $trace) {
            if (str_starts_with($trace['file'], __NAMESPACE__ . '\\')) {
                continue;
            }

            if (isset($this->instantiating[$className])) {
                throw new DebuggedCircularDependencyException(
                    $this->instantiating,
                    $className,
                    $trace['file'] . ':' . $trace['line']
                );
            }

            $this->instantiating[$className] = $trace['file'] . ':' . $trace['line'];
            return;
        }
    }

    private function getConstructorArgumentValues(string $className): array
    {
        $class = new \ReflectionClass($className);

        if (!$constructor = $class->getConstructor()) {
            return [];
        }

        return $this->parameterResolveManager->resolveMethod($constructor);
    }

    public function addResolver(ParameterResolver $parameterResolver): void
    {
        $this->parameterResolveManager->addResolver($parameterResolver);
    }
}
