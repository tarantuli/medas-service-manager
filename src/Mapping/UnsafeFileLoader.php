<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

use Medas\Core\Attributes\Service;

#[Service]
readonly class UnsafeFileLoader
{
    private FileFinder $fileFinder;

    public function __construct()
    {
        // This should not be a service, it is *not* instantiated automatically,
        // so don't add more dependencies, expecting them to be injected.
        $this->fileFinder = new FileFinder();
    }

    /**
     * @return bool Returns whether any files were loaded.
     */
    public function load(string $directory): bool
    {
        $files = $this->fileFinder->recursiveFindByExtension($directory, 'php');
        $loadedFile = false;

        foreach ($files as $file) {
            require_once $file;

            $loadedFile = true;
        }

        return $loadedFile;
    }
}
