<?php

declare(strict_types=1);

namespace kuiper\db;

class Criteria
{
    public const OPERATOR_EQUAL = '=';

    public const OPERATOR_IN = 'IN';

    public const STATEMENT_OR = '__OR__';

    public const STATEMENT_AND = '__AND__';

    /**
     * @var string[]
     */
    private $columns = [];

    /**
     * @var array
     */
    private $conditions = [];

    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    private $offset;

    /**
     * @var array
     */
    private $orderBy = [];

    /**
     * @var array
     */
    private $groupBy = [];

    /**
     * @var array
     */
    private $bindValues = [];

    public static function create(array $conditions = []): self
    {
        $criteria = new static();
        foreach ($conditions as $name => $value) {
            $criteria->where($name, $value);
        }

        return $criteria;
    }

    public function select(...$columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = $limit;
        $this->offset = $offset;

        return $this;
    }

    public function where(string $column, $value, string $op = self::OPERATOR_EQUAL): self
    {
        if (self::OPERATOR_EQUAL === $op) {
            $this->bindValues[$column] = $value;
        }
        if (empty($this->conditions)) {
            $this->conditions = [$column, $value, $op];
        } else {
            $this->conditions = [self::STATEMENT_AND, $this->conditions, [$column, $value, $op]];
        }

        return $this;
    }

    public function orWhere(string $column, $value, string $op = self::OPERATOR_EQUAL): self
    {
        if (empty($this->conditions)) {
            $this->where($column, $value, $op);
        } else {
            $this->conditions = [self::STATEMENT_OR, $this->conditions, [$column, $value, $op]];
        }

        return $$this;
    }

    public function or(Criteria $criteria): self
    {
        $this->conditions = [self::STATEMENT_AND, $this->conditions, $criteria->getConditions()];

        return $this;
    }

    public function and(Criteria $criteria): self
    {
        $this->conditions = [self::STATEMENT_AND, $this->conditions, $criteria->getConditions()];

        return $this;
    }

    public function orderBy(array $columns): self
    {
        $this->orderBy = $columns;

        return $this;
    }

    public function groupBy(array $columns): self
    {
        $this->groupBy = $columns;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getColumns(array $columnAlias = []): array
    {
        if ($this->columns && $columnAlias) {
            $cols = [];
            foreach ($this->columns as $column) {
                $cols[] = $columnAlias[$column] ?? $column;
            }

            return $cols;
        }

        return $this->columns;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    public function getBindValues(): array
    {
        return $this->bindValues;
    }

    public function getQuery(array $columnAlias = []): array
    {
        if (empty($this->conditions)) {
            return ['1=1'];
        }

        return $this->buildQuery($this->conditions, $columnAlias);
    }

    /**
     * 过滤查询条件
     * $callback 接受三个参数 function($column, $value, $operator), 也必须返回这三个参数构成的数组。
     *
     * @param callable $callback the callback
     *
     * @return Criteria
     */
    public function filter(callable $callback): self
    {
        $copy = clone $this;
        if ($copy->conditions) {
            $copy->conditions = $this->filterConditions($copy->conditions, $callback);
        }

        return $copy;
    }

    public function buildStatement(StatementInterface $stmt, array $columnAlias = []): StatementInterface
    {
        $stmt->where(...$this->getQuery($columnAlias));
        if ($this->getColumns()) {
            $stmt->select($this->getColumns($columnAlias));
        }
        if ($this->getLimit()) {
            $stmt->limit($this->getLimit())->offset($this->getOffset());
        }
        if ($this->getOrderBy()) {
            $stmt->orderBy($this->getOrderBy());
        }

        return $stmt;
    }

    private function buildQuery(array $conditions, array $columnAlias): array
    {
        if (in_array($conditions[0], [self::STATEMENT_AND, self::STATEMENT_OR], true)) {
            $left = $this->buildQuery($conditions[1], $columnAlias);
            $right = $this->buildQuery($conditions[2], $columnAlias);
            $stmtLeft = array_shift($left);
            $stmtRight = array_shift($right);
            $bindValues = array_merge($left, $right);
            $op = self::STATEMENT_AND === $conditions[0] ? 'AND' : 'OR';
            $stmt = sprintf('(%s) %s (%s)', $stmtLeft, $op, $stmtRight);
            array_unshift($bindValues, $stmt);

            return $bindValues;
        }

        [$column, $value, $op] = $conditions;
        $column = $columnAlias[$column] ?? $column;
        if (is_array($value) && in_array($op, [self::OPERATOR_EQUAL, self::OPERATOR_IN], true)) {
            $stmt = sprintf('%s IN (%s)', $column, implode(',', array_fill(0, count($value), '?')));

            return array_merge([$stmt], $value);
        }

        $stmt = sprintf('%s %s ?', $column, $op);

        return [$stmt, $value];
    }

    private function filterConditions(array $conditions, callable $callback): array
    {
        if (in_array($conditions[0], [self::STATEMENT_AND, self::STATEMENT_OR], true)) {
            return [
                $conditions[0],
                $this->filterConditions($conditions[1], $callback),
                $this->filterConditions($conditions[2], $callback),
            ];
        }

        $ret = call_user_func_array($callback, $conditions);
        if (is_array($ret) && 3 === count($ret)) {
            return $ret;
        }

        throw new \InvalidArgumentException('Callback should return valid statement');
    }
}
