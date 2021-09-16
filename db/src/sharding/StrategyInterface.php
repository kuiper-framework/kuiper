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

namespace kuiper\db\sharding;

interface StrategyInterface
{
    /**
     * Gets the partition table name.
     *
     * @param array  $fields the sharding values
     * @param string $table  the original table name
     *
     * @return string the table name
     */
    public function getTable(array $fields, string $table): string;

    /**
     * Gets the connection id.
     *
     * @param array $fields the sharding values
     *
     * @return int the connection id
     */
    public function getDb(array $fields): int;
}
