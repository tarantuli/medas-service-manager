<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\ServiceManager\Attributes\ConfigValue;
use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\ConfigOptions\ConfigOption;
use Medas\ServiceManager\ConfigOptions\OptionController;
use Medas\ServiceManager\Exceptions\ConfigValueDoesNotImplementOptionException;

#[Service]
class MethodArgumentsValueResolver
{
    private PreferredClassMap $preferredClassMap;

    private mixed $foundConfigValue;
    private object $foundClassValue;

    public function __construct(private ServiceManager $serviceManager)
    {
        // This service is *not* instantiated automatically, so don't add more dependencies,
        // expecting them to be injected.
    }

    public function resolve(\ReflectionMethod $method): array
    {
        $this->preferredClassMap = new PreferredClassMap($method);
        $arguments = [];

        foreach ($method->getParameters() as $parameter) {
            $arguments[] = $this->getMethodArgumentValue($parameter);
        }

        return $arguments;
    }

    private function getMethodArgumentValue(\ReflectionParameter $parameter): mixed
    {
        if ($this->findConfigValue($parameter)) {
            return $this->foundConfigValue;
        }

        if ($this->findClassValue($parameter)) {
            return $this->foundClassValue;
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new Exceptions\CouldNotResolveMethodArgumentException($parameter);
    }

    private function findConfigValue(\ReflectionParameter $parameter): bool
    {
        if (!$attributes = $parameter->getAttributes(ConfigValue::class)) {
            return false;
        }

        $option = $this->getConfigOption($attributes[0]);
        $optionController = $this->serviceManager->resolve(OptionController::class);

        if (!$optionController->hasValue($option)) {
            return false;
        }

        $this->foundConfigValue = $optionController->getValue($option);

        return true;
    }

    private function getConfigOption(\ReflectionAttribute $attribute): ConfigOption
    {
        $configOptionClass = $attribute->newInstance()->configOption;

        if (!class_exists($configOptionClass)) {
            throw new ConfigValueDoesNotImplementOptionException($configOptionClass);
        }
        /** @var ConfigOption $configOption */
        $configOption = $configOptionClass::instance();

        if (!$configOption instanceof ConfigOption) {
            throw new ConfigValueDoesNotImplementOptionException($configOptionClass);
        }
        return $configOption;
    }

    private function findClassValue(\ReflectionParameter $parameter): bool
    {
        $service = null;
        $types = $this->getParameterTypes($parameter);

        foreach ($types as $type) {
            $typeName = $type->getName();

            if ($preferredClass = $this->preferredClassMap->forType($typeName)) {
                if (null !== $this->serviceManager->findService($preferredClass)) {
                    $service = $preferredClass;
                    break;
                }
            }

            if (null !== $this->serviceManager->findService($typeName)) {
                $service = $typeName;
                break;
            }
        }

        if (null === $service) {
            return false;
        }

        $this->foundClassValue = $this->serviceManager->resolve($service);

        return true;
    }

    /** @return \ReflectionNamedType[] */
    private function getParameterTypes(\ReflectionParameter $parameter): array
    {
        $type = $parameter->getType();

        if (!$type) return [];

        return $type instanceof \ReflectionUnionType
            ? $type->getTypes()
            : [$type];
    }

}
