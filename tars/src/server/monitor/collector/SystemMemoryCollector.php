<?php

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
