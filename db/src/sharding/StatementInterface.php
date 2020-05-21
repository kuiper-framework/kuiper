<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

interface StatementInterface extends \kuiper\db\StatementInterface
{
    public function shardBy(array $fields);
}
