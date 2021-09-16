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

interface StatementInterface extends \kuiper\db\StatementInterface
{
    /**
     * Sets the sharding field values.
     *
     * @param array $fields shard by the values
     */
    public function shardBy(array $fields): void;

    public function getShardBy(): array;

    /**
     * Gets the original name of the table.
     */
    public function getBaseTable(): string;

    /**
     * Gets the sharding strategy.
     */
    public function getTableStrategy(): StrategyInterface;
}
