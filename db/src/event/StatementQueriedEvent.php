<?php

declare(strict_types=1);

namespace kuiper\db\event;

use kuiper\db\ConnectionInterface;
use kuiper\db\StatementInterface;

class StatementQueriedEvent extends AbstractEvent
{
    /**
     * @var StatementInterface
     */
    private $statement;

    /**
     * StatementQueriedEvent constructor.
     */
    public function __construct(ConnectionInterface $connection, StatementInterface $statement)
    {
        parent::__construct($connection);
        $this->statement = $statement;
    }

    public function getStatement(): StatementInterface
    {
        return $this->statement;
    }
}
