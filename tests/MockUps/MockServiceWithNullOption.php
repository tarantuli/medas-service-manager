<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\ServiceManager\Attributes\Service;

#[Service]
class MockServiceWithNullOption
{
    public function __construct(
        private readonly NonExistingClass|null $nonExistingClass
    )
    {
    }
}
