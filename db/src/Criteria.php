<?php

declare(strict_types=1);

namespace kuiper\db;

class Criteria
{
    public const OPERATOR_EQUAL = '=';

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
    private $bindValues = [];

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
            $this->conditions = ['AND', $this->conditions, [$column, $value, $op]];
        }

        return $this;
    }

    public function orWhere(string $column, $value, string $op = self::OPERATOR_EQUAL): self
    {
        if (empty($this->conditions)) {
            throw new \InvalidArgumentException('Expected where called');
        }
        $this->conditions = ['OR', $this->conditions, [$column, $value, $op]];
    }

    public function or(Criteria $criteria): self
    {
        $this->conditions = ['OR', $this->conditions, $criteria->getConditions()];

        return $this;
    }

    public function and(Criteria $criteria): self
    {
        $this->conditions = ['AND', $this->conditions, $criteria->getConditions()];

        return $this;
    }

    public function orderBy(array $columns): self
    {
        $this->orderBy = $columns;

        return $this;
    }

    public static function create(array $conditions = []): self
    {
        $criteria = new static();
        foreach ($conditions as $name => $value) {
            $criteria->where($name, $value);
        }

        return $criteria;
    }

    /**
     * @return string[]
     */
    public function getColumns(array $columnMap = []): array
    {
        if ($this->columns && $columnMap) {
            $cols = [];
            foreach ($this->columns as $column) {
                $cols[] = isset($columnMap[$column]) ? $columnMap[$column] : $column;
            }

            return $cols;
        }

        return $this->columns;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
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

    public function getQuery(array $columnMap = []): array
    {
        if (empty($this->conditions)) {
            return ['1=1'];
        }

        return $this->buildQuery($this->conditions, $columnMap);
    }

    /**
     * 过滤查询条件
     * $callback 接受三个参数 function($column, $value, $operator), 也必须返回这三个参数构成的数组。
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

    public function buildStatement(StatementInterface $stmt, array $columnMap = []): StatementInterface
    {
        $stmt->where(...$this->getQuery($columnMap));
        if ($this->getColumns()) {
            $stmt->select($this->getColumns($columnMap));
        }
        if ($this->getLimit()) {
            $stmt->limit($this->getLimit())->offset($this->getOffset());
        }
        if ($this->getOrderBy()) {
            $stmt->orderBy($this->getOrderBy());
        }

        return $stmt;
    }

    private function buildQuery(array $conditions, array $columnMap): array
    {
        if (in_array($conditions[0], ['AND', 'OR'], true)) {
            $left = $this->buildQuery($conditions[1], $columnMap);
            $right = $this->buildQuery($conditions[2], $columnMap);
            $stmtLeft = array_shift($left);
            $stmtRight = array_shift($right);
            $bindValues = array_merge($left, $right);
            $stmt = sprintf('(%s) %s (%s)', $stmtLeft, $conditions[0], $stmtRight);
            array_unshift($bindValues, $stmt);

            return $bindValues;
        }

        [$column, $value, $op] = $conditions;
        $column = isset($columnMap[$column]) ? $columnMap[$column] : $column;
        if (is_array($value) && in_array($op, [self::OPERATOR_EQUAL, 'in'], true)) {
            $stmt = sprintf('%s in (%s)', $column, implode(',', array_fill(0, count($value), '?')));

            return array_merge([$stmt], $value);
        }

        $stmt = sprintf('%s %s ?', $column, $op);

        return [$stmt, $value];
    }

    private function filterConditions(array $conditions, callable $callback): array
    {
        if (in_array($conditions[0], ['AND', 'OR'], true)) {
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
