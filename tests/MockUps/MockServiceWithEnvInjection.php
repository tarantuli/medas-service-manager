<?php

declare(strict_types=1);

namespace Medas\Test\MockUps;

use Medas\ServiceManager\Attributes\EnvValue;
use Medas\ServiceManager\Attributes\Service;

#[Service]
class MockServiceWithEnvInjection
{
    public function __construct(#[EnvValue('env')] private string $env)
    {
    }

    public function getEnv(): string
    {
        return $this->env;
    }
}
