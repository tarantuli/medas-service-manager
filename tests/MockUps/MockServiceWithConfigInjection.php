<?php

declare(strict_types=1);

namespace Medas\Test\MockUps;

use Medas\ServiceManager\Attributes\ConfigValue;
use Medas\ServiceManager\Attributes\Service;

#[Service]
class MockServiceWithConfigInjection
{
    public function __construct(#[ConfigValue('project')] private string $project)
    {
    }

    public function getProject(): string
    {
        return $this->project;
    }
}
