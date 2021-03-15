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
     * @var \PDOException|null
     */
    private $exception;

    /**
     * StatementQueriedEvent constructor.
     */
    public function __construct(StatementInterface $statement, \PDOException $exception = null)
    {
        $this->statement = $statement;
        $this->exception = $exception;
    }

    public function getStatement(): StatementInterface
    {
        return $this->statement;
    }

    public function getException(): ?\PDOException
    {
        return $this->exception;
    }
}
