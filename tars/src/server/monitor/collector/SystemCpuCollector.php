<?php

declare(strict_types=1);

namespace kuiper\tars\server\monitor\collector;

class SystemCpuCollector extends AbstractCollector
{
    public function getValues(): array
    {
        exec("command -v mpstat > /dev/null && mpstat -P ALL | awk '{if($12!=\"\") print $12}' | tail -n +3", $cpusInfo);
        $values = [];
        if (isset($cpusInfo)) {
            foreach ($cpusInfo as $key => $cpuInfo) {
                $values["systemCpu{$key}Usage"] = 100 - $cpuInfo;
            }
        }

        return $values;
    }
}
