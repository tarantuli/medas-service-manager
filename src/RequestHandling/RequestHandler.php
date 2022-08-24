<?php

declare(strict_types=1);

namespace Medas\ServiceManager\RequestHandling;

interface RequestHandler
{
    public function handle(string $method, string $path): mixed;
}
