<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

use kuiper\db\ConnectionInterface;

interface ConnectionFactoryInterface
{
    /**
     * Creates the connection.
     */
    public function create(int $connectionId): ConnectionInterface;
}
