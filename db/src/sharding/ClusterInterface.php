<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

use kuiper\db\ConnectionInterface;
use kuiper\db\QueryBuilderInterface;

interface ClusterInterface extends QueryBuilderInterface
{
    /**
     * @param int $id database index
     */
    public function getConnection($id): ConnectionInterface;

    /**
     * @param string $table
     */
    public function setTableStrategy($table, StrategyInterface $strategy): void;

    /**
     * @param string $table
     */
    public function getTableStrategy($table): StrategyInterface;
}
