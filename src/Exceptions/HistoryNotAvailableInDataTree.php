<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Exceptions;

use Medas\Core\Exceptions\BaseException;

class HistoryNotAvailableInDataTree extends BaseException
{
    public function __construct(string $index)
    {
        parent::__construct($index);
    }

    public function pattern(): string
    {
        return 'no history available for index %s: history is only recorded while the tree is built, not on a tree restored from cache';
    }
}
