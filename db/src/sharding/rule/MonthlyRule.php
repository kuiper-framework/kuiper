<?php

declare(strict_types=1);

namespace kuiper\db\sharding\rule;

class MonthlyRule extends AbstractRule
{
    protected function getPartitionFor($value)
    {
        $time = strtotime($value);
        if (false === $time) {
            throw new \InvalidArgumentException("Invalid date '$value'");
        }

        return date('ym', $time);
    }
}
