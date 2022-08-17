<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ConsoleCommands;

use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\Console\{BaseConsoleCommand,
    ConsoleCommandGroup,
    Printer\BashFormat,
    Printer\Printer,
    Printer\Text
};

#[Service]
class ListCommand extends BaseConsoleCommand
{
    public function __construct(
        private readonly Group        $group,
        private readonly Printer|null $printer,
    )
    {
    }

    public function group(): ConsoleCommandGroup
    {
        return $this->group;
    }

    public function name(): string
    {
        return 'list';
    }

    public function description(): string
    {
        return 'Prints a list of registered services';
    }

    public function process(array $arguments)
    {
        $this->printer
            ->printLine(
                new Text('Registered services: ')
            )
            ->printLine();

        $services = getPropertyValue(sm(), 'services');

        usort($services, fn(object $a, object $b) => strcasecmp($a::class, $b::class));

        foreach ($services as $service) {
            $this->printer->printLine(
                new Text('   ' . $service::class, BashFormat::LIGHT_BLUE)
            );
        }
    }
}
