<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\ServiceManager\Exceptions\ClassDependsOnUnknownClass;

abstract class BasePackage implements Package
{
    public function initialize(ServiceConfig $config): void
    {
        // Do nothing
    }

    public function postInstall(): void
    {
        // Do nothing
    }

    protected function dependenciesByClass(array $classNames): array
    {
        $results = [];

        foreach ($classNames as $className) {
            if (!class_exists($className)) {
                throw new ClassDependsOnUnknownClass($this::class, $className);
            }

            $class = new \ReflectionClass($className);
            $results[dirname($class->getFileName())] = $className::instance();
        }

        return $results;
    }
}
