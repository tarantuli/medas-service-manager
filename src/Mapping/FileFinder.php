<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

class FileFinder
{
    public function __construct()
    {
        // This should not be a service, it is *not* instantiated automatically,
        // so don't add more dependencies, expecting them to be injected.
    }

    public function recursiveFindByExtension(string $directory, string $extension): \Iterator
    {
        $ext = ltrim($extension, '.');
        $suffix = '.' . strtolower($ext);

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $directory,
            \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS
        ));

        return new \CallbackFilterIterator(
            $iterator,
            static function (mixed $current) use ($suffix): bool {
            if (!is_string($current)) {
                return false;
            }

            return str_ends_with(strtolower($current), $suffix);
        });
    }
}
