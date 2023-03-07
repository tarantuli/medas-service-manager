<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Interfaces;

interface ObjectSerializer
{
    public function serialize(object $object): array;

    /**
     * This method should return an array of unserialized properties. Use patchObject() to apply those properties to an
     * object.
     */
    public function unserialize(string $className, array $properties): array;

    /**
     * This method should unserialize() the properties, and then set the values on the object.
     */
    public function patchObject(object $object, array $properties): void;
}
