<?php

declare(strict_types=1);

namespace kuiper\helper;

function env(string $name, ?string $default = null): ?string
{
    return $_ENV[$name] ?? $_SERVER[$name] ?? $default;
}
