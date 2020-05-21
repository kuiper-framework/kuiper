<?php

declare(strict_types=1);

namespace kuiper\db\event;

use kuiper\db\ConnectionInterface;

class SqlPreparedEvent extends AbstractEvent
{
    /**
     * @var string
     */
    private $sql;

    /**
     * SqlExecutedEvent constructor.
     */
    public function __construct(ConnectionInterface $connection, string $sql)
    {
        parent::__construct($connection);
        $this->sql = $sql;
    }

    public function getSql(): string
    {
        return $this->sql;
    }
}
