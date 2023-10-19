<?php

declare(strict_types=1);

namespace kuiper\metrics\registry;

use kuiper\helper\Text;

abstract class NamingStrategy implements NamingStrategyInterface
{
    public function name(string $name): string
    {
        return $name;
    }

    public function tagKey(string $key): string
    {
        return $key;
    }

    public function tagValue(string $value): string
    {
        return $value;
    }

    public static function snakeCase(): NamingStrategyInterface
    {
        return new class() extends NamingStrategy {
            public function name(string $name): string
            {
                return Text::snakeCase($name);
            }

            public function tagKey(string $key): string
            {
                return Text::snakeCase($key);
            }
        };
    }

    public static function identity(): NamingStrategyInterface
    {
        return new class() extends NamingStrategy {
        };
    }
}
