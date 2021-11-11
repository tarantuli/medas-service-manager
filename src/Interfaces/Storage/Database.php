<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Interfaces\Storage;

interface Database
{
    public function createTable(TableBlueprint $blueprint): Table;

    public function getTable(string $name): Table;
}
