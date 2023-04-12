<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps\CircularDependencies;

use Medas\ServiceManager\Service;

#[Service]
class DirectDependency1
{
    public function __construct(
        private DirectDependency2 $directDependency2,
    )
    {
    }
}
