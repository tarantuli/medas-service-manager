<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ErrorHandling;

interface ExceptionHandler
{
    public function handleException(\Throwable $throwable): void;
}
