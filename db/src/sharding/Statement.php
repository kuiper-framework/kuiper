<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\UpdateInterface;
use kuiper\db\constants\SqlState;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

class Statement extends \kuiper\db\Statement
{
    /**
     * @var ClusterInterface
     */
    private $cluster;

    /**
     * @var string
     */
    private $table;

    /**
     * @var StrategyInterface
     */
    private $strategy;

    /**
     * @var bool
     */
    private $autoCreateTable;

    /**
     * @var array
     */
    private $shardBy = [];

    public function __construct(ClusterInterface $cluster, $query, $table, StrategyInterface $strategy, $autoCreateTable, EventDispatcherInterface $eventDispatcher = null)
    {
        $this->cluster = $cluster;
        $this->query = $query;
        $this->table = $table;
        $this->strategy = $strategy;
        $this->autoCreateTable = $autoCreateTable;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function shardBy(array $fields)
    {
        $this->shardBy = $fields;

        return $this;
    }

    public function cols(array $fields)
    {
        if (is_array($fields)) {
            $this->shardBy = array_merge($this->shardBy, $fields);
        }

        return parent::cols($fields);
    }

    public function where($condition, ...$args)
    {
        if (is_array($condition)) {
            $cols = [];
            foreach ($condition as $key => $val) {
                $cols[$key] = is_array($val) && isset($val[0]) ? $val[0] : $val;
            }
            $this->shardBy = array_merge($this->shardBy, $cols);
        }

        return call_user_func_array('parent::where', func_get_args());
    }

    protected function doQuery()
    {
        Assert::notEmpty($this->shardBy, 'Sharding fields are empty');
        $this->connection = $this->cluster->getConnection($this->strategy->getDb($this->shardBy));
        $table = $this->strategy->getTable($this->shardBy, $this->table);
        if ($this->query instanceof SelectInterface || $this->query instanceof DeleteInterface) {
            $this->query->from($table);
        } elseif ($this->query instanceof UpdateInterface) {
            $this->query->table($table);
        } elseif ($this->query instanceof InsertInterface) {
            $this->query->into($table);
        }
        try {
            return parent::doQuery();
        } catch (\PDOException $e) {
            if ($this->autoCreateTable && SqlState::BAD_TABLE == $e->getCode()) {
                $sql = sprintf('CREATE TABLE IF NOT EXISTS `%s` LIKE `%s`', $table, $this->table);
                $this->connection->exec($sql);

                return parent::doQuery();
            }
            throw $e;
        }
    }

    public function getShardBy()
    {
        return $this->shardBy;
    }
}
