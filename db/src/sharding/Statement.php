<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

use Aura\SqlQuery\QueryInterface;
use kuiper\db\constants\SqlState;
use kuiper\db\event\ShardTableNotExistEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

    public function __construct(ClusterConnectionPool $cluster, QueryInterface $query, string $table, StrategyInterface $strategy, EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct($cluster, $query, $eventDispatcher);
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
    }

    public function getShardBy(): array
    {
        return $this->shardBy;
    }

    public function cols(array $fields): \kuiper\db\StatementInterface
    {
        if (is_array($fields)) {
            $this->shardBy = array_merge($this->shardBy, $fields);
        }

        return parent::cols($fields);
    }

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

    protected function doQuery(): bool
    {
        if (empty($this->shardBy)) {
            throw new \InvalidArgumentException('Sharding fields are empty');
        }
        $this->pool->setConnectionId($this->strategy->getDb($this->shardBy));
        $table = $this->strategy->getTable($this->shardBy, $this->table);
        $this->table($table);
        try {
            return parent::doQuery();
        } catch (\PDOException $e) {
            if (SqlState::BAD_TABLE === $e->getCode()) {
                /** @var ShardTableNotExistEvent $event */
                $event = $this->eventDispatcher->dispatch(new ShardTableNotExistEvent($this, $table));
                if ($event->isTableCreated()) {
                    return parent::doQuery();
                }
            }
            throw $e;
        }
    }
}
