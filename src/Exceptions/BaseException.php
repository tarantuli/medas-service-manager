<?php

declare(strict_types=1);

namespace Medas\ServiceContainer\Exceptions;

use Medas\ServiceContainer\Core\Str;

abstract class BaseException extends \Exception
{
    private ?\Exception $previous = null;

    public function __construct(...$arguments)
    {
        foreach ($arguments as &$argument) {
            $argument = Str::fromVariable($argument)->truncateToCharLength(255);
        }

        $message = vsprintf($this->getPattern(), $arguments);

        parent::__construct($message, 1, $this->previous);
    }

    abstract public function getPattern(): string;

    public function setPrevious(?\Exception $previous): self
    {
        $this->previous = $previous;

        return $this;
    }
}
