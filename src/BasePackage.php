<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\ServiceManager\Exceptions\ClassDependsOnUnknownClassException;
use Medas\ServiceManager\Interfaces\Package;

abstract class BasePackage implements Package
{
    protected function dependenciesByClass(array $classNames): array
    {
        $results = [];

        foreach ($classNames as $className) {
            if (!class_exists($className)) {
                throw new ClassDependsOnUnknownClassException($this::class, $className);
            }

            $class = new \ReflectionClass($className);
            $results[dirname($class->getFileName())] = new $className;
        }

        return $results;
    }
}
