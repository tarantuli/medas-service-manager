<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Interfaces;

interface GuidProvider
{
    public function create(): Guid;

    public function fromBytes(string $bytes): Guid;

    public function fromString(string $string): Guid;
}
