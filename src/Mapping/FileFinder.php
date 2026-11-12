<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

/**
 * Lists the files with a given extension in a directory and all its subdirectories.
 */
class FileFinder
{
    public function __construct()
    {
        // This should not be a service, it is *not* instantiated automatically,
        // so don't add more dependencies, expecting them to be injected.
    }

    /**
     * @param string $extension With or without the leading dot, matched case-insensitively.
     *
     * @return \Iterator<string> Full paths, found as the iterator is consumed.
     */
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
