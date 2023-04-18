<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps\CircularDependencies;

use Medas\Core\Attributes\Service;

#[Service]
class IndirectDependency1
{
    public function __construct(
        private IndirectDependency2 $indirectDependency2,
    )
    {
    }
}
