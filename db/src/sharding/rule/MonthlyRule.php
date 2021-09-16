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
