<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps\Instantiation;

use Medas\ServiceManagerTest\MockUps\Logger;

class ClassWithArgument
{
    public function __construct(
        private readonly Logger $logger,
        private readonly int    $number,
    )
    {
    }
}
