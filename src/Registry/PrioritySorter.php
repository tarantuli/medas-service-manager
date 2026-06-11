<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Registry;

readonly class PrioritySorter
{
    public function sort(array $classes): array
    {
        $services = namesToServices($classes);

        uasort($services, fn($a, $b) => -$a->priority() <=> $b->priority());

        return servicesToNames($services);
    }
}
