<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

interface RuleInterface
{
    /**
     * @return int|string
     */
    public function getPartition(array $fields);
}
