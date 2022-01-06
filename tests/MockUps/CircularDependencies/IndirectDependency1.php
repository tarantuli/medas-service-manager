<?php

declare(strict_types=1);

namespace Medas\Test\MockUps\CircularDependencies;

use Medas\ServiceManager\Attributes\Service;

#[Service]
class IndirectDependency1
{
    public function __construct(private IndirectDependency2 $indirectDependency2)
    {
    }
}
