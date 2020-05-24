<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

use kuiper\db\ConnectionInterface;
use kuiper\db\QueryBuilderInterface;

interface ClusterInterface extends QueryBuilderInterface
{
    /**
     * @param int $connectionId database index
     */
    public function getConnection(int $connectionId): ConnectionInterface;

    public function getTableStrategy(string $table): StrategyInterface;
}
