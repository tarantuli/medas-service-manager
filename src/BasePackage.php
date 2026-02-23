<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

use Medas\Core\Interfaces\{Package, ServiceConfig};

abstract class BasePackage implements Package
{
    public function priority(): int
    {
        return 0;
    }

    public function initialize(ServiceConfig $config): void
    {
        // Do nothing
    }

    public function postInstall(): void
    {
        // Do nothing
    }

    public function hasMarkdownDocumentation(): bool
    {
        return false;
    }

    public function markdownDocumentationDirectory(): string
    {
        return '../documentation';
    }
}
