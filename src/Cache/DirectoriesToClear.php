<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Cache;

use Medas\Core\Attributes\Service;

/**
 * Empties the directories registered in var/dirs-to-clear: one file per directory, named after a hash of the path and
 * containing the path. File system caches register their directories there, and so does the APCu cache for its
 * generation markers - emptying that directory is the only way a command-line process can make the web server's APCu
 * cache start afresh.
 *
 * The post-install script bin/post-install-scripts/clear-cache-dirs.php uses this too, so the clear-caches console
 * command clears the same caches as a composer install.
 */
#[Service]
readonly class DirectoriesToClear
{
    /**
     * @param string $registryDirectory Relative to the project root, which medas/core makes the working directory
     */
    public function __construct(
        private string $registryDirectory = 'var/dirs-to-clear',
    )
    {
    }

    /**
     * Empties every registered directory, leaving the directories themselves in place.
     *
     * @return string[] The directories that were emptied
     */
    public function clear(): array
    {
        if (!is_dir($this->registryDirectory)) {
            return [];
        }

        $cleared = [];

        foreach (scandir($this->registryDirectory) as $registryFile) {
            $registryPath = $this->registryDirectory . DIRECTORY_SEPARATOR . $registryFile;

            if (!is_file($registryPath)) {
                continue;
            }

            $directory = realpath(trim(file_get_contents($registryPath)));

            if ($directory === false || !is_dir($directory) || $this->isDangerous($directory)) {
                continue;
            }

            $this->empty($directory);

            $cleared[] = $directory;
        }

        return $cleared;
    }

    /**
     * Refuses file system roots, and the working directory (the project root) or any of its parents, which a
     * corrupted or mistaken registration could otherwise wipe out.
     */
    private function isDangerous(string $directory): bool
    {
        if (dirname($directory) === $directory) {
            return true;
        }

        $workingDirectory = getcwd();

        if ($workingDirectory === false) {
            return true;
        }

        $directoryWithSeparator = rtrim($directory, '\\/') . DIRECTORY_SEPARATOR;
        $workingDirectoryWithSeparator = rtrim($workingDirectory, '\\/') . DIRECTORY_SEPARATOR;

        return str_starts_with($workingDirectoryWithSeparator, $directoryWithSeparator);
    }

    private function empty(string $directory): void
    {
        $entries = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($entries as $entry) {
            // Entries can disappear while this runs (e.g. a concurrent request replacing a cache entry), and on Windows
            // a file that another process has open can't be deleted; neither is a reason to stop
            if ($entry->isDir() && !$entry->isLink()) {
                @rmdir($entry->getPathname());
            }
            else {
                @unlink($entry->getPathname());
            }
        }
    }
}
