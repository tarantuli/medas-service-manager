<?php

declare(strict_types=1);

namespace Medas\Test\MockUps;

use Medas\ServiceContainer\Attributes\Service;
use Medas\ServiceContainer\Interfaces\Logger;

#[Service]
class DefaultLogger implements Logger
{

}
