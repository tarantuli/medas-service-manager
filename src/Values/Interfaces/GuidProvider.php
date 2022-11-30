<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Values\Interfaces;

interface GuidProvider
{
    public function create(): Guid;
}
