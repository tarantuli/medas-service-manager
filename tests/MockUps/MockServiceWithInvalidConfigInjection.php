<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\MockUps;

use Medas\ServiceManager\Attributes\ConfigValue;
use Medas\ServiceManager\Attributes\Service;

#[Service]
class MockServiceWithInvalidConfigInjection
{
    public function __construct(#[ConfigValue('project')] private string $project)
    {
    }
}
