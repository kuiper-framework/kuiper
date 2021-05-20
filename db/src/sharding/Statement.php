<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

use Aura\SqlQuery\QueryInterface;
use kuiper\db\constants\SqlState;
use kuiper\db\event\ShardTableNotExistEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class Statement extends \kuiper\db\Statement implements StatementInterface
{
    /**
     * @var StrategyInterface
     */
    private $strategy;

    /**
     * @var array
     */
    private $shardBy = [];

    /**
     * @var ClusterConnectionPool
     */
    private $cluster;

    /**
     * @var string
     */
    private $baseTable;

    public function __construct(ClusterConnectionPool $cluster, QueryInterface $query, string $table, StrategyInterface $strategy, EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct($cluster, $query, $eventDispatcher);
        $this->cluster = $cluster;
        $this->baseTable = $table;
        $this->strategy = $strategy;
    }

    public function getBaseTable(): string
    {
        return $this->baseTable;
    }

    public function getTableStrategy(): StrategyInterface
    {
        return $this->strategy;
    }

    /**
     * {@inheritdoc}
     */
    public function shardBy(array $fields): void
    {
        $this->shardBy = $fields;
        $this->setTable();
    }

    protected function setTable(): void
    {
        if (empty($this->shardBy)) {
            throw new \InvalidArgumentException('Sharding fields are empty');
        }
        $connectionId = $this->strategy->getDb($this->shardBy);
        if ($this->cluster->hasConnection() && $connectionId !== $this->cluster->getConnectionId()) {
            throw new \InvalidArgumentException('connection not consist with previous');
        }
        $this->cluster->setConnectionId($connectionId);
        if (method_exists($this->query, 'resetTables')) {
            $this->query->resetTables();
        }
        $this->table($this->getTableName());
    }

    public function tableAlias(string $alias): \kuiper\db\StatementInterface
    {
        $this->tableAlias = $alias;

        return $this;
    }

    public function getShardBy(): array
    {
        return $this->shardBy;
    }

    /**
     * {@inheritDoc}
     */
    public function cols(array $values): \kuiper\db\StatementInterface
    {
        $this->shardBy = array_merge($this->shardBy, $values);

        return parent::cols($values);
    }

    /**
     * {@inheritDoc}
     */
    public function addRow(array $values = []): \kuiper\db\StatementInterface
    {
        if (!empty($values)) {
            $this->shardBy = array_merge($this->shardBy, $values);
        }

        return parent::addRow($values);
    }

    /**
     * {@inheritdoc}
     */
    public function where($condition, ...$args): \kuiper\db\StatementInterface
    {
        if (is_array($condition)) {
            $cols = [];
            foreach ($condition as $key => $val) {
                $cols[$key] = is_array($val) && isset($val[0]) ? $val[0] : $val;
            }
            $this->shardBy = array_merge($this->shardBy, $cols);
        }

        return parent::where($condition, ...$args);
    }

    protected function getTableName(): string
    {
        if (empty($this->shardBy)) {
            throw new \InvalidArgumentException('Sharding fields are empty');
        }

        return $this->strategy->getTable($this->shardBy, $this->baseTable);
    }

    protected function doQuery(): bool
    {
        if (!$this->cluster->hasConnection()) {
            $this->setTable();
        }
        try {
            return parent::doQuery();
        } catch (\PDOException $e) {
            if (SqlState::BAD_TABLE === $e->getCode()) {
                /** @var ShardTableNotExistEvent $event */
                $event = $this->eventDispatcher->dispatch(new ShardTableNotExistEvent($this, $this->getTableName()));
                if ($event->isTableCreated()) {
                    return parent::doQuery();
                }
            }
            throw $e;
        }
    }
}
