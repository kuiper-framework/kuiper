<?php

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

    /**
     * Gets the original name of the table.
     */
    public function getBaseTable(): string;

    /**
     * Gets the sharding strategy.
     */
    public function getTableStrategy(): StrategyInterface;
}
