<?php

declare(strict_types=1);

namespace Medas\ServiceManager\RequestHandling;

interface GeneratesEndpoint
{
    public function endpoint(array $arguments = []): string;
}
