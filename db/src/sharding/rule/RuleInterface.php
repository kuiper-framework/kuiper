<?php

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
    public function getPartition(array $fields);
}
