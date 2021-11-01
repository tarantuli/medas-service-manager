<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Interfaces;

interface EnvManager
{
    public function setRoot(string $rootDirectory): void;

    public function getEnv(): ?string;

    public function setEnv(string $env): void;

    public function getValue(string $path): mixed;

    public function hasValue(string $path): bool;

    public function getFiles(): array;
}
