<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ConsoleCommands;

use Medas\Console\{Commands\BaseConsoleCommand, Commands\ConsoleCommandGroup, Formats\Style, Printer, Text};
use Medas\ServiceManager\Attributes\Service;

#[Service]
class ListCommand extends BaseConsoleCommand
{
    public function __construct(
        private readonly Group   $group,
        private readonly Printer $printer,
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

    public function process(array $arguments): void
    {
        $this->printer
            ->print(
                Text::create('Registered services: ')
            )
            ->print();

        $services = getPropertyValue(sm(), 'services');

        usort($services, fn(object $a, object $b) => strcasecmp($a::class, $b::class));

        foreach ($services as $service) {
            $this->printer->print(
                Text::create('   ' . $service::class, Style::Bold)
            );
        }
    }
}
