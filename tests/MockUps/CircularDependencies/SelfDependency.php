<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps\CircularDependencies;

use Medas\Core\Attributes\Service;

#[Service]
class SelfDependency
{
    public function __construct(
        private readonly SelfDependency $selfDependency,
    )
    {
    }
}
