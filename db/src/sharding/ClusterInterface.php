<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

use kuiper\db\QueryBuilderInterface;

interface ClusterInterface extends QueryBuilderInterface
{
    public function getTableStrategy(string $table): StrategyInterface;
}
