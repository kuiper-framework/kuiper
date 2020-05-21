<?php

declare(strict_types=1);

namespace kuiper\db\event;

use kuiper\db\ConnectionInterface;

class SqlQueriedEvent extends AbstractEvent
{
    /**
     * @var string
     */
    private $sql;

    /**
     * @var array
     */
    private $params;

    /**
     * SqlExecutedEvent constructor.
     */
    public function __construct(ConnectionInterface $connection, string $sql, array $params)
    {
        parent::__construct($connection);
        $this->sql = $sql;
        $this->params = $params;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
