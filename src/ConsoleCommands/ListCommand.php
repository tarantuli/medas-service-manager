<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ConsoleCommands;

use Medas\Console\Commands\BaseConsoleCommand;
use Medas\Console\Commands\ConsoleCommandGroup;
use Medas\Console\Printer;
use Medas\ServiceManager\Attributes\Service;

#[Service]
class ListCommand extends BaseConsoleCommand
{
    public function __construct(
        private ServicesGroup $group,
        private Printer       $printer,
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
                new Printer\Text('Registered services: ')
            )
            ->printLine();

        $services = getPropertyValue(sm(), 'services');

        usort($services, fn(object $a, object $b) => strcasecmp($a::class, $b::class));

        foreach ($services as $service) {
            $this->printer->printLine(
                new Printer\Text('   ' . $service::class, Printer\BashFormat::LIGHT_BLUE)
            );
        }
    }
}
