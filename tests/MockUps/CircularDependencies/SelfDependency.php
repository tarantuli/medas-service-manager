<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps\CircularDependencies;

use Medas\ServiceManager\Attributes\Service;

#[Service]
class SelfDependency
{
    public function __construct(
        private readonly SelfDependency $selfDependency,
    )
    {
    }
}
