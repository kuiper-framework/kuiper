<?php

declare(strict_types=1);

namespace kuiper\db;

use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\UpdateInterface;
use Aura\SqlQuery\QueryInterface;
use kuiper\db\event\StatementQueriedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Interface StatementInterface.
 *
 * @method leftJoin(string $table, string $cond): StatementInterface
 * @method bindValues(array $bindValues): StatementInterface
 * @method union(): StatementInterface
 * @method unionAll(): StatementInterface
 */
class Statement implements StatementInterface
{
    private const OPERATOR_OR = 'OR';
    private const OPERATOR_AND = 'AND';

    /**
     * @var ConnectionPoolInterface
     */
    protected $pool;

    /**
     * @var QueryInterface
     */
    protected $query;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var \PDOStatement|null
     */
    protected $pdoStatement;

    /**
     * @var string|null
     */
    protected $table;

    /**
     * @var string|null
     */
    protected $tableAlias;

    /**
     * @var float
     */
    private $startTime;

    public function __construct(ConnectionPoolInterface $pool, QueryInterface $query, EventDispatcherInterface $eventDispatcher)
    {
        $this->pool = $pool;
        $this->query = $query;
        $this->eventDispatcher = $eventDispatcher;
        $this->startTime = microtime(true);
    }

    public function __destruct()
    {
        $this->close();
    }

    public function __toString()
    {
        return $this->getStatement();
    }

    public function getStartTime(): float
    {
        return $this->startTime;
    }

    public function close(): void
    {
        if ($this->pdoStatement) {
            $this->pdoStatement->closeCursor();
            $this->pdoStatement = null;
        }
        if ($this->connection) {
            $this->connection = null;
        }
    }

    public function table(string $table): StatementInterface
    {
        $this->table = $table;
        if ($this->query instanceof SelectInterface || $this->query instanceof DeleteInterface) {
            $this->query->from($table.($this->tableAlias ? ' as '.$this->tableAlias : ''));
        } elseif ($this->query instanceof UpdateInterface) {
            $this->query->table($table);
        } elseif ($this->query instanceof InsertInterface) {
            $this->query->into($table);
        } else {
            throw new \InvalidArgumentException('unknown query type '.get_class($this->query));
        }

        return $this;
    }

    protected function getTableName(): string
    {
        return $this->table;
    }

    public function useIndex(string $indexName): StatementInterface
    {
        if (!$this->query instanceof SelectInterface) {
            throw new \InvalidArgumentException('Cannot not call use index for '.get_class($this->query));
        }
        $this->query->resetTables();
        $this->query->fromRaw($this->getTableName().($this->tableAlias ? ' as '.$this->tableAlias : '')
            ." use index ({$indexName})");

        return $this;
    }

    public function tableAlias(string $alias): StatementInterface
    {
        $this->query->resetTables();
        $this->tableAlias = $alias;
        $this->table($this->getTableName());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function select(...$columns): StatementInterface
    {
        $this->query->resetCols();
        $this->query->cols($columns);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function where($condition, ...$args): StatementInterface
    {
        if (is_string($condition)) {
            call_user_func_array([$this->query, 'WHERE'], func_get_args());
        } elseif (is_array($condition)) {
            $this->addWhere($condition);
        } else {
            throw new \InvalidArgumentException('Expected array or string, got '.gettype($condition));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue(string $name, $value): StatementInterface
    {
        $this->query->bindValue($name, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function orWhere($condition, ...$args): StatementInterface
    {
        if (is_string($condition)) {
            call_user_func_array([$this->query, 'orWhere'], func_get_args());
        } elseif (is_array($condition)) {
            $this->addWhere($condition, self::OPERATOR_OR);
        } else {
            throw new \InvalidArgumentException('Expected array or string, got '.gettype($condition));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function like(string $field, string $value): StatementInterface
    {
        return $this->where($field.' LIKE ?', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function in(string $field, array $values): StatementInterface
    {
        if (empty($values)) {
            throw new \InvalidArgumentException('Cannot bind empty value for in statment');
        }

        return $this->addInWhere($field, $values);
    }

    /**
     * {@inheritdoc}
     */
    public function orIn(string $field, array $values): StatementInterface
    {
        if (empty($values)) {
            throw new \InvalidArgumentException('Cannot bind empty value for in statment');
        }

        return $this->addInWhere($field, $values, self::OPERATOR_OR);
    }

    /**
     * {@inheritdoc}
     */
    public function notIn(string $field, array $values): StatementInterface
    {
        if (empty($values)) {
            throw new \InvalidArgumentException('Cannot bind empty value for in statment');
        }

        return $this->addInWhere($field, $values, self::OPERATOR_AND, 'not IN');
    }

    /**
     * {@inheritdoc}
     */
    public function orNotIn(string $field, array $values): StatementInterface
    {
        if (empty($values)) {
            throw new \InvalidArgumentException('Cannot bind empty value for in statment');
        }

        return $this->addInWhere($field, $values, self::OPERATOR_OR, 'not IN');
    }

    /**
     * {@inheritdoc}
     */
    public function cols(array $values): StatementInterface
    {
        $this->query->cols($values);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addRow(array $values): StatementInterface
    {
        $this->query->addRow($values);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function limit($limit): StatementInterface
    {
        $this->query->limit($limit);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function offset($offset): StatementInterface
    {
        if (method_exists($this->query, 'offset')) {
            $this->query->offset($offset);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy(array $orderSpec): StatementInterface
    {
        if (empty($orderSpec)) {
            $this->query->resetOrderBy();
        } else {
            $this->query->orderBy($orderSpec);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(array $columns): StatementInterface
    {
        if (empty($columns)) {
            $this->query->resetGroupBy();
        } else {
            $this->query->groupBy($columns);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): bool
    {
        return $this->doQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function query(): StatementInterface
    {
        $this->doQuery();

        return $this;
    }

    public function __call($method, $args)
    {
        call_user_func_array([$this->query, $method], $args);

        return $this;
    }

    public function getConnection(): ConnectionInterface
    {
        if (null === $this->connection) {
            $this->connection = $this->pool->take();
        }

        return $this->connection;
    }

    public function getStatement(): string
    {
        return (string) $this->query->getStatement();
    }

    public function getPdoStatement(): \PDOStatement
    {
        $this->checkPdoStatement();

        return $this->pdoStatement;
    }

    public function getBindValues(): array
    {
        return $this->query->getBindValues();
    }

    /**
     * {@inheritdoc}
     */
    public function rowCount(): int
    {
        $this->checkPdoStatement();

        return $this->pdoStatement->rowCount();
    }

    public function fetch(int $fetchStyle = null)
    {
        $this->checkPdoStatement();

        return $this->pdoStatement->fetch($fetchStyle);
    }

    public function fetchColumn(int $columnNumber = 0)
    {
        $this->checkPdoStatement();

        return $this->pdoStatement->fetchColumn($columnNumber);
    }

    public function fetchAll(int $fetchStyle = null)
    {
        $this->checkPdoStatement();

        return $this->pdoStatement->fetchAll($fetchStyle);
    }

    protected function doQuery(): bool
    {
        try {
            $result = $this->doQueryOnce();
            $this->eventDispatcher->dispatch(new StatementQueriedEvent($this));

            return $result;
        } catch (\PDOException $e) {
            if (Connection::isRetryableError($e)) {
                $this->connection->disconnect();
                $this->connection->connect();
                try {
                    $result = $this->doQueryOnce();
                    $this->eventDispatcher->dispatch(new StatementQueriedEvent($this));

                    return $result;
                } catch (\PDOException $e) {
                    $this->eventDispatcher->dispatch(new StatementQueriedEvent($this, $e));
                    throw $e;
                }
            } else {
                $this->eventDispatcher->dispatch(new StatementQueriedEvent($this, $e));
                throw $e;
            }
        }
    }

    protected function doQueryOnce(): bool
    {
        $this->pdoStatement = $this->getConnection()->prepare($this->query->getStatement());

        return @$this->pdoStatement->execute($this->query->getBindValues());
    }

    protected function addWhere(array $condition, $op = self::OPERATOR_AND): void
    {
        foreach ($condition as $key => $value) {
            if (is_array($value)) {
                if (self::OPERATOR_OR === $op) {
                    throw new \InvalidArgumentException('orWhere does not support in operator yet');
                }
                $this->in($key, $value);
                unset($condition[$key]);
            }
        }
        if (!empty($condition)) {
            $cond = '('.implode(' AND ', array_map(static function ($field) {
                return $field.'=?';
            }, array_keys($condition))).')';
            $args = array_values($condition);
            array_unshift($args, $cond);
            if (self::OPERATOR_AND === $op) {
                $this->query->where(...$args);
            } else {
                $this->query->orWhere(...$args);
            }
        }
    }

    protected function addInWhere($field, array $values, $op = self::OPERATOR_AND, $in = 'IN'): self
    {
        if (!empty($values)) {
            $condition = sprintf('%s %s (%s)', $field, $in, implode(',', array_fill(0, count($values), '?')));
            if (self::OPERATOR_AND === $op) {
                $this->query->where($condition, ...$values);
            } else {
                $this->query->orWhere($condition, ...$values);
            }
        }

        return $this;
    }

    protected function checkPdoStatement(): void
    {
        if (null === $this->pdoStatement) {
            throw new \BadMethodCallException('statement is not available');
        }
    }
}
