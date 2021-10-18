<?php

declare(strict_types=1);

namespace Medas\Test\MockUps;

use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\Interfaces\Logger;

#[Service]
class DefaultLogger implements Logger
{

}
