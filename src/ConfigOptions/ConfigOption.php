<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ConfigOptions;

interface ConfigOption
{
    public static function instance(): ConfigOption;

    public function group(): ConfigGroup;

    public function name(): string;

    public function description(): string;

    public function isValid(mixed $value): bool;

    public function hasDefault(): bool;

    public function default(): mixed;
}
