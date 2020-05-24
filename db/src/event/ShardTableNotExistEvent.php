<?php

declare(strict_types=1);

namespace kuiper\db\event;

use kuiper\db\sharding\StatementInterface;
use kuiper\event\StoppableEventTrait;
use Psr\EventDispatcher\StoppableEventInterface;

class ShardTableNotExistEvent implements StoppableEventInterface
{
    use StoppableEventTrait;

    /**
     * @var StatementInterface
     */
    private $statement;
    /**
     * @var string
     */
    private $table;
    /**
     * @var bool
     */
    private $tableCreated = false;

    public function __construct(StatementInterface $statement, string $table)
    {
        $this->statement = $statement;
        $this->table = $table;
    }

    public function getStatement(): StatementInterface
    {
        return $this->statement;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function isTableCreated(): bool
    {
        return $this->tableCreated;
    }

    public function setTableCreated(bool $tableCreated): void
    {
        $this->tableCreated = $tableCreated;
    }
}
