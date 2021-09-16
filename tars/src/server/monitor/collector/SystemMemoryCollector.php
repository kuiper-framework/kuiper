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

namespace kuiper\tars\server\monitor\collector;

class SystemMemoryCollector extends AbstractCollector
{
    public function getValues(): array
    {
        exec('free -m | grep Mem', $sysMemInfo);
        preg_match_all("/\d+/", $sysMemInfo[0], $matches);
        if (isset($matches[0][0])) {
            return ['systemMemoryUsage' => $matches[0][0]];
        }

        return [];
    }
}
