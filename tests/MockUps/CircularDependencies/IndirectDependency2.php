<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps\CircularDependencies;

use Medas\ServiceManager\Attributes\Service;

#[Service]
class IndirectDependency2
{
    public function __construct(private IndirectDependency3 $indirectDependency3)
    {
    }
}
