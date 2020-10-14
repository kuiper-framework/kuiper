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
     * @var string
     */
    private $table;

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

    public function __construct(ClusterConnectionPool $cluster, QueryInterface $query, string $table, StrategyInterface $strategy, EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct($cluster, $query, $eventDispatcher);
        $this->cluster = $cluster;
        $this->table = $table;
        $this->strategy = $strategy;
    }

    public function getTable(): string
    {
        return $this->table;
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
        $connectionId = $this->strategy->getDb($this->shardBy);
        if ($this->cluster->hasConnection() && $connectionId !== $this->cluster->getConnectionId()) {
            throw new \InvalidArgumentException('connection not consist with previous');
        }
        $this->cluster->setConnectionId($connectionId);
        $this->table($this->getTableName());
    }

    public function getShardBy(): array
    {
        return $this->shardBy;
    }

    public function cols(array $fields): \kuiper\db\StatementInterface
    {
        $this->shardBy = array_merge($this->shardBy, $fields);

        return parent::cols($fields);
    }

    public function addRow(array $fields = []): \kuiper\db\StatementInterface
    {
        if (!empty($fields)) {
            $this->shardBy = array_merge($this->shardBy, $fields);
        }

        return parent::addRow($fields);
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
        return $this->strategy->getTable($this->shardBy, $this->table);
    }

    protected function doQuery(): bool
    {
        if (empty($this->shardBy)) {
            throw new \InvalidArgumentException('Sharding fields are empty');
        }
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
