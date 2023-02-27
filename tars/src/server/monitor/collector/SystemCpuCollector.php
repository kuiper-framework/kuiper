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

class SystemCpuCollector extends AbstractCollector
{
    public function getValues(): array
    {
        exec("command -v mpstat > /dev/null && mpstat -P ALL | awk '{if($12!=\"\") print $12}' | tail -n +3", $cpusInfo);
        $values = [];
        foreach ($cpusInfo as $key => $cpuInfo) {
            $values["systemCpu{$key}Usage"] = 100 - (int) $cpuInfo;
        }

        return $values;
    }
}
