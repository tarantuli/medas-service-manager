<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Exceptions;

use Medas\Core\Exceptions\BaseException;

class DebuggedCircularDependencyException extends BaseException
{
    public function __construct(array $trace, string $current, string $sourceFile)
    {
        $circle = ["$current => $sourceFile"];

        foreach ($trace as $class => $sourceFile) {
            $circle[] = "$class => $sourceFile";
        }
        parent::__construct(implode("\n*  ", $circle));
    }

    public function pattern(): string
    {
        return "circular dependency found:\n*  %s";
    }
}
