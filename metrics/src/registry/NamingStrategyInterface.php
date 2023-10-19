<?php

declare(strict_types=1);

namespace kuiper\metrics\registry;

interface NamingStrategyInterface
{
    public function name(string $name): string;

    public function tagKey(string $key): string;

    public function tagValue(string $value): string;
}
