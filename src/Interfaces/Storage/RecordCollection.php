<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Interfaces\Storage;

interface RecordCollection extends \Iterator
{
    public function current(): Record;
}
