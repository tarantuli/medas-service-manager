<?php

declare(strict_types=1);

namespace Medas\Test\MockUps;

use Medas\ServiceManager\Attributes\Service;

#[Service]
class MockServiceWithNullOption
{
    public function __construct(
        private NonExistingClass|null $nonExistingClass
    )
    {
    }
}
