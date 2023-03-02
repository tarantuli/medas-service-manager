<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Interfaces;

interface DirectoryManager
{
    public function loadPhpFiles(string $directory): void;

    public function recursiveFindByExtension(string $directory, string $extension, string $ignorePattern = null): iterable;

    public function recursiveFind(string $directory, string $matchPattern, string $ignorePattern = null): iterable;

    public function create(string $path): void;
}
