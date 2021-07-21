<?php

declare(strict_types=1);

namespace kuiper\helper;

function env($name, $default = null)
{
    return $_ENV[$name] ?? $_SERVER[$name] ?? $default;
}
