<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps\CircularDependencies;

use Medas\ServiceManager\Attributes\Service;

#[Service]
class DirectDependency2
{
    public function __construct(
        private DirectDependency1 $directDependency1,
    )
    {
    }
}
