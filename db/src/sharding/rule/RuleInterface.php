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

interface RuleInterface
{
    /**
     * Gets the sharding partition.
     *
     * @param array $fields sharding fields
     *
     * @return int|string
     */
    public function getPartition(array $fields): int|string;
}
