<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\helper;

if (!function_exists('kuiper\\helper\\env')) {
    function env(string $name, ?string $default = null): ?string
    {
        return $_ENV[$name] ?? $_SERVER[$name] ?? $default;
    }
}
