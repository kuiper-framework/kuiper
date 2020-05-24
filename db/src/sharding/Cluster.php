<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

use Aura\SqlQuery\QueryFactory;
use Aura\SqlQuery\QueryInterface;
use kuiper\db\Connection;
use kuiper\db\ConnectionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Cluster implements ClusterInterface
{
    /**
     * @var ConnectionFactoryInterface
     */
    private $connectionFactory;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var array
     */
    private $tables;

    /**
     * @var Connection[]
     */
    private $connections;

    public function __construct(ConnectionFactoryInterface $connectionFactory, QueryFactory $queryFactory, EventDispatcherInterface $eventDispatcher)
    {
        $this->connectionFactory = $connectionFactory;
        $this->queryFactory = $queryFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(int $connectionId): ConnectionInterface
    {
        if (!isset($this->connections[$connectionId])) {
            $this->connections[$connectionId] = $this->connectionFactory->create($connectionId);
        }
    }

    public function setTableStrategy(string $table, StrategyInterface $strategy): void
    {
        $this->tables[$table] = $strategy;
    }

    /**
     * {@inheritdoc}
     */
    public function getTableStrategy(string $table): StrategyInterface
    {
        return $this->tables[$table];
    }

    public function getQueryFactory(): QueryFactory
    {
        return $this->queryFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function from(string $table): \kuiper\db\StatementInterface
    {
        return $this->createStatement($table, $query = $this->getQueryFactory()->newSelect());
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $table): \kuiper\db\StatementInterface
    {
        return $this->createStatement($table, $query = $this->getQueryFactory()->newDelete());
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $table): \kuiper\db\StatementInterface
    {
        return $this->createStatement($table, $query = $this->getQueryFactory()->newUpdate());
    }

    /**
     * {@inheritdoc}
     */
    public function insert(string $table): \kuiper\db\StatementInterface
    {
        return $this->createStatement($table, $query = $this->getQueryFactory()->newInsert());
    }

    protected function createStatement(string $table, QueryInterface $query): StatementInterface
    {
        if (!isset($this->tables[$table])) {
            throw new \InvalidArgumentException("Table '{$table}' strategy was not configured, call setTableStrategy first");
        }

        return new Statement($this, $query, $table, $this->tables[$table], $this->eventDispatcher);
    }
}
