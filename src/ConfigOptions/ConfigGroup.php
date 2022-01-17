<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ConfigOptions;

interface ConfigGroup
{
    public static function instance(): ConfigGroup;

    public function parent(): ConfigGroup|null;

    public function name(): string;
}
