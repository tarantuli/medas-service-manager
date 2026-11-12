<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

/**
 * Loads the classes in a package directory through the Composer autoloader, using the PSR-4 mapping in the
 * package's `composer.json`.
 *
 * @assumes `composer.json` is in the parent of the directory, like `src` next to `composer.json`. Without it, or
 *          without a PSR-4 mapping in it, nothing is loaded, so the package has no services.
 * @sideEffect Triggers an `E_USER_WARNING` when `composer.json` is missing or unreadable, or has no PSR-4
 *             mapping.
 * @sideEffect Autoloads every class, interface, trait and enum whose file is in the directory. A file whose path
 *             doesn't match its class name according to PSR-4 isn't loaded.
 * @notFor Test packages; `UnsafeFileLoader` loads those.
 */
readonly class Psr4FileLoader
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
        $directoryReal = realpath($directory) ?: $directory;

        // Resolve PSR-4 mappings from composer.json and "autoload" them via Composer
        // (i.e., trigger the autoloader with class_exists()) instead of require_once'ing files.
        $composerJson = dirname($directoryReal) . DIRECTORY_SEPARATOR . 'composer.json';

        if (!is_file($composerJson)) {
            return $this->warn($directory, "there's no $composerJson");
        }

        $composer = json_decode((string) file_get_contents($composerJson), true);

        if (!is_array($composer)) {
            return $this->warn($directory, "$composerJson can't be read as JSON");
        }

        $psr4 = $composer['autoload']['psr-4'] ?? [];

        if (!is_array($psr4) || $psr4 === []) {
            return $this->warn($directory, "$composerJson has no PSR-4 autoload mapping");
        }

        $loadedAny = false;

        foreach ($psr4 as $namespacePrefix => $paths) {
            $paths = is_array($paths) ? $paths : [$paths];

            foreach ($paths as $path) {
                if ($this->processPath($path, $composerJson, $directoryReal, $namespacePrefix)) {
                    $loadedAny = true;
                }
            }
        }

        return $loadedAny;
    }

    /**
     * Warns that the package's services can't be discovered. A warning, not an exception, because a package without
     * services works fine otherwise.
     *
     * @return false So callers can return the result directly
     */
    private function warn(string $directory, string $reason): bool
    {
        trigger_error(
            sprintf('No services can be discovered in %s, because %s', $directory, $reason),
            E_USER_WARNING
        );

        return false;
    }

    private function processPath(
        string|null $path,
        string      $composerJson,
        string      $directoryReal,
        string      $namespacePrefix
    ): bool
    {
        if (!is_string($path) || $path === '') {
            return false;
        }

        $baseDir = dirname($composerJson)
            . DIRECTORY_SEPARATOR
            . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        $baseDirReal = realpath($baseDir) ?: $baseDir;

        // Only consider mappings that point into the scanned directory.
        if (!str_starts_with($baseDirReal, $directoryReal) && !str_starts_with($directoryReal, $baseDirReal)) {
            return false;
        }

        $files = $this->fileFinder->recursiveFindByExtension($baseDirReal, 'php');
        $loadedAny = false;

        foreach ($files as $file) {
            $fileReal = realpath($file) ?: $file;

            // Keep the scan restricted to the requested $directory.
            if (!str_starts_with($fileReal, $directoryReal)) {
                continue;
            }

            $relative = substr($fileReal, strlen(rtrim($baseDirReal, DIRECTORY_SEPARATOR)) + 1);

            if ($relative === '') {
                continue;
            }

            // PSR-4: NamespacePrefix + relative path (without .php), with separators as backslashes.
            if (!str_ends_with($relative, '.php')) {
                continue;
            }

            $relativeClass = substr($relative, 0, -4);
            $relativeClass = str_replace(DIRECTORY_SEPARATOR, '\\', $relativeClass);
            $className = rtrim($namespacePrefix, '\\') . '\\' . ltrim($relativeClass, '\\');

            // Trigger Composer autoloader (does not error if the class is absent).
            if (class_exists($className) || interface_exists($className) || trait_exists($className)) {
                $loadedAny = true;
            }
        }

        return $loadedAny;
    }
}
