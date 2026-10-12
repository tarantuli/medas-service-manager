<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ConsoleCommands;

use Medas\Console\{
    Commands\Argument,
    Commands\BaseConsoleCommand,
    Commands\CommandInput,
    Commands\ConsoleCommandGroup,
    Formats\Decoration,
    Formats\SafeColor,
    Printer,
    Text
};
use Medas\Core\Attributes\Service;
use Medas\ServiceManager\ServiceManager;

#[Service]
readonly class ListServices extends BaseConsoleCommand
{
    public function __construct(
        private Group          $group,
        private Printer        $printer,
        private ServiceManager $serviceManager,
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

    public function arguments(): array
    {
        return [
            new Argument('filter', required: false, default: null),
        ];
    }

    public function process(CommandInput $input): void
    {
        $this->printer
            ->printEol()
            ->printLine(Text::create('Registered services', SafeColor::White))
            ->printEol();

        $services = $this->serviceManager->getServiceClassNames();

        usort($services, fn(string $a, string $b) => strcasecmp($a, $b));

        $printedSomething = false;
        $filterValue = $input->getArgument('filter');

        foreach ($services as $service) {
            if (isset($filterValue) && !str_contains($service, $filterValue)) {
                continue;
            }

            $blocks = [Text::create('   ')];

            foreach (explode('\\', $service) as $part) {
                if (count($blocks) > 1) {
                    $blocks[] = Text::create('\\', SafeColor::Gray);
                }

                $blocks[] = Text::create($part, SafeColor::Green);
            }

            $this->printer->printLine(...$blocks);

            $printedSomething = true;
        }

        if (!$printedSomething) {
            if (isset($filterValue)) {
                $this->printer->printLine(
                    Text::create('   no services found that match: ', SafeColor::LightRed),
                    Text::create($filterValue, Decoration::Bold),
                );
            }
            else {
                $this->printer->printLine(
                    Text::create('   no services found', SafeColor::LightRed),
                );
            }
        }
    }
}
