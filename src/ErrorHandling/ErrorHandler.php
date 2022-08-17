<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

interface ErrorHandler
{
    public function set(): void;
}
