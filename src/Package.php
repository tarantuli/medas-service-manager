<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\Interfaces\IsSingleton;

interface Package extends IsSingleton
{
    public function priority(): int;

    /** @return Package[] */
    public function dependencies(): array;

    public function sourceDirectory(): string;

    public function initialize(ServiceConfig $config): void;

    public function postInstall(): void;

    /**
     * Whether the package has a directory containing markdown documentation, starting with an index.md file.
     */
    public function hasMarkdownDocumentation(): bool;

    /**
     * The path to the directory containing markdown documentation relative to the source directory.
     */
    public function markdownDocumentationDirectory(): string;
}
