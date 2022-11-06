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

use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\Insert;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\Select;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\Update;
use Aura\SqlQuery\Common\UpdateInterface;
use Aura\SqlQuery\Common\WhereInterface;
use Aura\SqlQuery\QueryInterface;
use BadMethodCallException;
use InvalidArgumentException;
use kuiper\db\constants\ErrorCode;
use kuiper\db\event\StatementQueriedEvent;
use PDOException;
use PDOStatement;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Interface StatementInterface.
 *
 * @method $this leftJoin(string $table, string $cond)
 * @method $this bindValues(array $bindValues)
 * @method $this set(string $column, string $expression)
 * @method $this union()
 * @method $this unionAll()
 */
class Statement implements StatementInterface
{
    private const OPERATOR_OR = 'OR';
    private const OPERATOR_AND = 'AND';

    private float $startTime;

    private ?PDOStatement $pdoStatement = null;

    private ?string $table = null;

    private ?string $tableAlias = null;

    private ?string $lastInsertId = null;

    public function __construct(
        private readonly ConnectionPoolInterface $pool,
        private readonly QueryInterface $query,
        private readonly EventDispatcherInterface $eventDispatcher)
    {
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

    /**
     * @return string|null
     */
    public function getLastInsertId(): ?string
    {
        return $this->lastInsertId;
    }

    protected function getQuery(): QueryInterface
    {
        return $this->query;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function close(): void
    {
        if (null !== $this->pdoStatement) {
            $this->pdoStatement->closeCursor();
            $this->pdoStatement = null;
        }
        unset($this->lastInsertId);
    }

    public function withConnection(callable $call): void
    {
        $conn = $this->pool->take();
        try {
            $call($conn);
        } finally {
            $this->pool->release($conn);
        }
    }

    public function table(string $table): static
    {
        $this->table = $table;
        if ($this->query instanceof SelectInterface || $this->query instanceof DeleteInterface) {
            $this->query->from($table.(null !== $this->tableAlias ? ' as '.$this->tableAlias : ''));
        } elseif ($this->query instanceof UpdateInterface) {
            $this->query->table($table);
        } elseif ($this->query instanceof InsertInterface) {
            $this->query->into($table);
        } else {
            throw new InvalidArgumentException('unknown query type '.get_class($this->query));
        }

        return $this;
    }

    protected function getTableName(): string
    {
        return $this->table;
    }

    /** @noinspection PhpUnused */
    public function useIndex(string $indexName): static
    {
        $query = $this->query;
        if (!$query instanceof SelectInterface) {
            throw new InvalidArgumentException('Cannot not call use index for '.get_class($query));
        }
        /** @var Select $query */
        $query->resetTables();
        $query->fromRaw($this->getTableName().(null !== $this->tableAlias ? ' as '.$this->tableAlias : '')
            ." use index ($indexName)");

        return $this;
    }

    protected function setTableAlias(string $tableAlias): void
    {
        $this->tableAlias = $tableAlias;
    }

    public function tableAlias(string $alias): static
    {
        /** @var Select $query */
        $query = $this->query;
        $query->resetTables();
        $this->tableAlias = $alias;
        $this->table($this->getTableName());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function select(...$columns): static
    {
        /** @var Select $query */
        $query = $this->query;
        $query->resetCols();
        $query->cols($columns);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function where(mixed $condition, ...$args): static
    {
        if (is_string($condition)) {
            $this->query->where($condition, ...$args);
        } elseif (is_array($condition)) {
            $this->addWhere($condition);
        } else {
            throw new InvalidArgumentException('Expected array or string, got '.gettype($condition));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue(string $name, mixed $value): static
    {
        $this->query->bindValue($name, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function orWhere(mixed $condition, ...$args): static
    {
        if (is_string($condition)) {
            call_user_func_array([$this->query, 'orWhere'], func_get_args());
        } elseif (is_array($condition)) {
            $this->addWhere($condition, self::OPERATOR_OR);
        } else {
            throw new InvalidArgumentException('Expected array or string, got '.gettype($condition));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function like(string $column, string $value): static
    {
        return $this->where($column.' LIKE ?', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function in(string $column, array $values): static
    {
        if (empty($values)) {
            throw new InvalidArgumentException('Cannot bind empty value for in statment');
        }

        return $this->addInWhere($column, $values);
    }

    /**
     * {@inheritdoc}
     */
    public function orIn(string $column, array $values): static
    {
        if (empty($values)) {
            throw new InvalidArgumentException('Cannot bind empty value for in statment');
        }

        return $this->addInWhere($column, $values, self::OPERATOR_OR);
    }

    /**
     * {@inheritdoc}
     */
    public function notIn(string $column, array $values): static
    {
        if (empty($values)) {
            throw new InvalidArgumentException('Cannot bind empty value for in statment');
        }

        return $this->addInWhere($column, $values, self::OPERATOR_AND, 'not IN');
    }

    /**
     * {@inheritdoc}
     */
    public function orNotIn(string $column, array $values): static
    {
        if (empty($values)) {
            throw new InvalidArgumentException('Cannot bind empty value for in statment');
        }

        return $this->addInWhere($column, $values, self::OPERATOR_OR, 'not IN');
    }

    /**
     * {@inheritdoc}
     */
    public function cols(array $values): static
    {
        /** @var Insert|Update $query */
        $query = $this->query;
        $query->cols($values);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addRow(array $values = []): static
    {
        /** @var Insert $query */
        $query = $this->query;
        $query->addRow($values);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function limit(int $limit): static
    {
        /** @var Select $query */
        $query = $this->query;
        $query->limit($limit);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function offset(int $offset): static
    {
        if (method_exists($this->query, 'offset')) {
            $this->query->offset($offset);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy(array $orderSpec): static
    {
        /** @var Select $query */
        $query = $this->query;
        if (empty($orderSpec)) {
            $query->resetOrderBy();
        } else {
            $query->orderBy($orderSpec);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(array $columns): static
    {
        /** @var Select $query */
        $query = $this->query;
        if (empty($columns)) {
            $query->resetGroupBy();
        } else {
            $query->groupBy($columns);
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
    public function query(): static
    {
        $this->doQuery();

        return $this;
    }

    /**
     * @return static
     */
    public function __call(string $method, array $args)
    {
        call_user_func_array([$this->query, $method], $args);

        return $this;
    }

    public function getStatement(): string
    {
        return (string) $this->query->getStatement();
    }

    /** @noinspection PhpUnused */
    public function getPdoStatement(): PDOStatement
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

    public function fetch(int $fetchStyle = null): mixed
    {
        $this->checkPdoStatement();

        return $this->pdoStatement->fetch($fetchStyle);
    }

    public function fetchColumn(int $columnNumber = 0): mixed
    {
        $this->checkPdoStatement();

        return $this->pdoStatement->fetchColumn($columnNumber);
    }

    public function fetchAll(int $fetchStyle = null): array
    {
        $this->checkPdoStatement();

        if (isset($fetchStyle)) {
            return $this->pdoStatement->fetchAll($fetchStyle);
        }

        return $this->pdoStatement->fetchAll();
    }

    protected function doQuery(): bool
    {
        $conn = $this->pool->take();
        try {
            $result = $this->doQueryOnce($conn);
            $this->eventDispatcher->dispatch(new StatementQueriedEvent($this));

            return $result;
        } catch (PDOException $e) {
            if (ErrorCode::isRetryable($e)) {
                $conn->disconnect();
                $conn->connect();
                try {
                    $result = $this->doQueryOnce($conn);
                    $this->eventDispatcher->dispatch(new StatementQueriedEvent($this));

                    return $result;
                } catch (PDOException $e) {
                    $this->eventDispatcher->dispatch(new StatementQueriedEvent($this, $e));
                    throw $e;
                }
            } else {
                $this->eventDispatcher->dispatch(new StatementQueriedEvent($this, $e));
                throw $e;
            }
        } finally {
            $this->pool->release($conn);
        }
    }

    private function doQueryOnce(ConnectionInterface $conn): bool
    {
        $this->pdoStatement = $conn->prepare($this->query->getStatement());
        foreach ($this->query->getBindValues() as $key => $value) {
            if (is_int($value)) {
                $this->pdoStatement->bindValue(':'.$key, $value, \PDO::PARAM_INT);
            } else {
                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format('Y-m-d H:i:s');
                }
                $this->pdoStatement->bindValue(':'.$key, $value);
            }
        }
        $result = @$this->pdoStatement->execute();
        if ($this->query instanceof InsertInterface) {
            try {
                $this->lastInsertId = $conn->lastInsertId();
            } catch (PDOException) {
                // pass
            }
        }

        return $result;
    }

    protected function addWhere(array $condition, string $op = self::OPERATOR_AND): void
    {
        foreach ($condition as $key => $value) {
            if (is_array($value)) {
                if (self::OPERATOR_OR === $op) {
                    throw new InvalidArgumentException('orWhere does not support in operator yet');
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

            /** @var WhereInterface $query */
            $query = $this->query;
            if (self::OPERATOR_AND === $op) {
                $query->where($cond, ...$args);
            } else {
                $query->orWhere($cond, ...$args);
            }
        }
    }

    protected function addInWhere(string $field, array $values, string $op = self::OPERATOR_AND, string $in = 'IN'): static
    {
        if (!empty($values)) {
            $condition = sprintf('%s %s (%s)', $field, $in, implode(',', array_fill(0, count($values), '?')));
            /** @var WhereInterface $query */
            $query = $this->query;
            if (self::OPERATOR_AND === $op) {
                $query->where($condition, ...$values);
            } else {
                $query->orWhere($condition, ...$values);
            }
        }

        return $this;
    }

    protected function checkPdoStatement(): void
    {
        if (null === $this->pdoStatement) {
            throw new BadMethodCallException('statement is not available');
        }
    }
}
