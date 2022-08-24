<?php

declare(strict_types=1);

namespace Medas\ServiceManager\RequestHandling;

interface RequestHandlerManager
{
    public function find(string $method, string $path): RequestHandler|null;

    public function findByName(string $name): RequestHandler|null;
}
