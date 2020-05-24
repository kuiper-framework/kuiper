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

    public function getCluster(): ClusterInterface;

    public function getTable(): string;

    public function getTableStrategy(): StrategyInterface;
}
