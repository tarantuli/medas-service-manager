<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\Core\Attributes\Service;

#[Service]
readonly class MockServiceWithNullOption
{
    public function __construct(
        private NonExistingClass|null $nonExistingClass
    )
    {
    }
}
