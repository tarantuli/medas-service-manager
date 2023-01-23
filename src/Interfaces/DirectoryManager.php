<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Interfaces;

interface DirectoryManager
{
    public function loadPhpFiles(string $directory): void;

    public function recursiveFindByExtension(string $directory, string $extension): \RegexIterator;

    public function recursiveFind(string $directory, string $pattern): \RegexIterator;

    public function create(string $path): void;
}
