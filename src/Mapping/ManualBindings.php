<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

class ManualBindings
{
    public \SplObjectStorage $storage;

    public function set(string $class, string $parameter, mixed $value, string $method = '__construct'): void
    {
        $parameterReflectors = (new \ReflectionClass($class))->getMethod($method)->getParameters();

        foreach ($parameterReflectors as $parameterReflector) {
            if ($parameterReflector->name === $parameter) {
                $this->storage[$parameterReflector] = $value;

                return;
            }
        }
    }
}
