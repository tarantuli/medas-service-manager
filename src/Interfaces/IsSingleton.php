<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Interfaces;

interface IsSingleton
{
    public static function instance(): self;
}
