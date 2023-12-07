<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ConsoleCommands;

use Medas\Console\{
    Commands\BaseConsoleCommand,
    Commands\ConsoleCommandGroup,
    Formats\Color,
    Formats\Style,
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

    public function process(array $arguments): void
    {
        $this->printer
            ->printEol()
            ->printLine(Text::create('Registered services', Color::White))
            ->printEol();

        $services = $this->serviceManager->getServiceClassNames();

        usort($services, fn(string $a, string $b) => strcasecmp($a, $b));

        $printedSomething = false;

        foreach ($services as $service) {
            if (isset($arguments[1]) && !str_contains($service, $arguments[1])) {
                continue;
            }

            $blocks = [Text::create('   ')];

            foreach (explode('\\', $service) as $part) {
                if (count($blocks) > 1) {
                    $blocks[] = Text::create('\\', Color::Gray);
                }

                $blocks[] = Text::create($part, Color::Green);
            }

            $this->printer->printLine(...$blocks);

            $printedSomething = true;
        }

        if (!$printedSomething) {
            if (isset($arguments[1])) {
                $this->printer->printLine(
                    Text::create('   no services found that match: ', Color::LightRed),
                    Text::create($arguments[1], Style::Bold),
                );
            }
            else {
                $this->printer->printLine(
                    Text::create('   no services found', Color::LightRed),
                );
            }
        }
    }
}
