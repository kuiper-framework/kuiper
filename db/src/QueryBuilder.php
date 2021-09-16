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

namespace kuiper\db;

use Aura\SqlQuery\QueryFactory;
use Aura\SqlQuery\QueryInterface;
use kuiper\event\NullEventDispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;

class QueryBuilder implements QueryBuilderInterface
{
    /**
     * @var ConnectionPoolInterface
     */
    private $pool;
    /**
     * @var QueryFactory
     */
    private $queryFactory;
    /**
     * @var EventDispatcherInterface|null
     */
    private $eventDispatcher;

    public function __construct(ConnectionPoolInterface $pool, ?QueryFactory $queryFactory, ?EventDispatcherInterface $eventDispatcher)
    {
        $this->pool = $pool;
        $this->eventDispatcher = $eventDispatcher ?? new NullEventDispatcher();
        if (null !== $queryFactory) {
            $this->queryFactory = $queryFactory;
        } else {
            $connection = $this->pool->take();
            $this->queryFactory = new QueryFactory($connection->getAttribute(\PDO::ATTR_DRIVER_NAME));
        }
    }

    public function getConnectionPool(): ConnectionPoolInterface
    {
        return $this->pool;
    }

    public function getQueryFactory(): QueryFactory
    {
        return $this->queryFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function from(string $table): StatementInterface
    {
        return $this->createStatement($table, $this->getQueryFactory()->newSelect());
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $table): StatementInterface
    {
        return $this->createStatement($table, $this->getQueryFactory()->newDelete());
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $table): StatementInterface
    {
        return $this->createStatement($table, $this->getQueryFactory()->newUpdate());
    }

    /**
     * {@inheritdoc}
     */
    public function insert(string $table): StatementInterface
    {
        return $this->createStatement($table, $this->getQueryFactory()->newInsert());
    }

    protected function createStatement(string $table, QueryInterface $query): StatementInterface
    {
        return (new Statement($this->pool, $query, $this->eventDispatcher))->table($table);
    }
}
