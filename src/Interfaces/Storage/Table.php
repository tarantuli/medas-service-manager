<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Interfaces\Storage;

interface Table
{
    public function getByValues(array $values): RecordCollection;
}
