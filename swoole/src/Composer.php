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

namespace kuiper\swoole;

use InvalidArgumentException;

class Composer
{
    public static function getJson(string $file = null): array
    {
        if (null === $file) {
            $file = self::detect();
        }
        if (!is_readable($file)) {
            throw new InvalidArgumentException('Cannot read composer.json');
        }
        $json = json_decode(file_get_contents($file), true);
        if (empty($json)) {
            throw new InvalidArgumentException("invalid composer.json read from $file");
        }

        return $json;
    }

    public static function detect(string $basePath = null): string
    {
        if (null === $basePath) {
            $basePath = getcwd();
        }
        while (!file_exists($basePath.'/composer.json')) {
            $parentDir = dirname($basePath);
            if ($parentDir === $basePath) {
                throw new InvalidArgumentException('Cannot detect project path, is there composer.json in current directory?');
            }
            $basePath = $parentDir;
        }

        return $basePath.'/composer.json';
    }
}
