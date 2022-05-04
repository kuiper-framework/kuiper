<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\db\sharding;

use Aura\SqlQuery\QueryFactory;
use Aura\SqlQuery\QueryInterface;
use kuiper\db\ConnectionPoolInterface;
use kuiper\event\EventDispatcherAwareInterface;
use kuiper\event\EventDispatcherAwareTrait;
use Psr\EventDispatcher\EventDispatcherInterface;

class Cluster implements ClusterInterface, EventDispatcherAwareInterface
{
    use EventDispatcherAwareTrait;

    /**
     * @var array
     */
    private array $tables;

    public function __construct(
        private readonly array $poolList,
        private readonly QueryFactory $queryFactory,
        EventDispatcherInterface $eventDispatcher)
    {
        $this->setEventDispatcher($eventDispatcher);
    }

    public function getConnectionPool(int $connectionId): ConnectionPoolInterface
    {
        if (!isset($this->poolList[$connectionId])) {
            throw new \InvalidArgumentException("unknown connection $connectionId");
        }

        return $this->poolList[$connectionId];
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
        return $this->createStatement($table, $this->getQueryFactory()->newSelect());
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $table): \kuiper\db\StatementInterface
    {
        return $this->createStatement($table, $this->getQueryFactory()->newDelete());
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $table): \kuiper\db\StatementInterface
    {
        return $this->createStatement($table, $this->getQueryFactory()->newUpdate());
    }

    /**
     * {@inheritdoc}
     */
    public function insert(string $table): \kuiper\db\StatementInterface
    {
        return $this->createStatement($table, $this->getQueryFactory()->newInsert());
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

    protected function createStatement(string $table, QueryInterface $query): \kuiper\db\StatementInterface
    {
        if (!isset($this->tables[$table])) {
            throw new \InvalidArgumentException("Table '{$table}' strategy was not configured, call setTableStrategy first");
        }

        return new Statement(new ClusterConnectionPool($this->poolList), $query, $table, $this->tables[$table], $this->eventDispatcher);
    }
}
