<?php

declare(strict_types=1);

namespace kuiper\db\event;

use kuiper\db\StatementInterface;

class StatementQueriedEvent
{
    /**
     * @var StatementInterface
     */
    private $statement;

    /**
     * StatementQueriedEvent constructor.
     */
    public function __construct(StatementInterface $statement)
    {
        $this->statement = $statement;
    }

    public function getStatement(): StatementInterface
    {
        return $this->statement;
    }
}
