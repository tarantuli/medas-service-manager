<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Interfaces\Storage;

interface Table
{
    public function update(TableBlueprint $blueprint): void;

    public function drop(): void;

    public function getRecord(array $filters): Record;
}
