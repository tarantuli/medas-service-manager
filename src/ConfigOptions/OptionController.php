<?php

declare(strict_types=1);

namespace Medas\ServiceManager\ConfigOptions;

use Medas\ServiceManager\Attributes\Service;
use Medas\ServiceManager\Interfaces\ConfigManager;
use Medas\ServiceManager\Interfaces\Unserializer;
use Medas\ServiceManager\Interfaces\Validator;

#[Service]
class OptionController
{
    public function __construct(
        private ConfigManager $configManager,
    )
    {
    }

    public function hasValue(ConfigOption $option): bool
    {
        return $option->hasDefault()
            || $this->configManager->hasValue($this->getPath($option));
    }

    public function getValue(ConfigOption $option): mixed
    {
        $path = $this->getPath($option);

        if ($this->configManager->hasValue($path)) {
            $value = $this->configManager->getValue($path);

            if ($option instanceof Validator && !$option->isValid($value)) {
                /** @noinspection PhpParamsInspection $option is most certainly also a ConfigOption */
                throw new Exceptions\InvalidValueException($value, $option);
            }

            if ($option instanceof Unserializer) {
                $value = $option->unserialize($value);
            }

            return $value;
        }

        elseif ($option->hasDefault()) {
            return $option->default();
        }

        throw new Exceptions\NoConfigValueFoundException($option);
    }

    public function getPath(ConfigOption $option): string
    {
        $path = $option->name();
        $group = $option->group();

        do {
            $path = $group->name() . '.' . $path;
        } while ($group = $group->parent());

        return $path;
    }
}
