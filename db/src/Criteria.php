<?php

declare(strict_types=1);

namespace kuiper\db;

use kuiper\db\criteria\AndClause;
use kuiper\db\criteria\CriteriaClauseInterface;
use kuiper\db\criteria\ExpressionClause;
use kuiper\db\criteria\ExpressionClauseFilterInterface;
use kuiper\db\criteria\LogicClause;
use kuiper\db\criteria\NotClause;
use kuiper\db\criteria\OrClause;
use kuiper\db\criteria\RawClause;
use kuiper\db\criteria\Sort;

class Criteria
{
    public const OPERATOR_EQUAL = '=';

    public const OPERATOR_IN = 'IN';

    public const OPERATOR_NOT_IN = 'NOT IN';

    public const OPERATOR_LIKE = 'LIKE';

    public const OPERATOR_NOT_LIKE = 'NOT LIKE';

    /**
     * @var string[]
     */
    private $columns = [];

    /**
     * @var CriteriaClauseInterface
     */
    private $conditions;

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

        return $this->merge(new ExpressionClause($column, $op, $value));
    }

    public function expression(string $expression, ...$bindValues): self
    {
        return $this->merge(new RawClause($expression, $bindValues));
    }

    public function like(string $column, $value): self
    {
        return $this->where($column, $value, self::OPERATOR_LIKE);
    }

    public function notLike(string $column, $value): self
    {
        return $this->where($column, $value, self::OPERATOR_NOT_LIKE);
    }

    public function in(string $column, $value): self
    {
        return $this->where($column, $value, self::OPERATOR_IN);
    }

    public function notIn(string $column, $value): self
    {
        return $this->where($column, $value, self::OPERATOR_NOT_IN);
    }

    public function orWhere(string $column, $value, string $op = self::OPERATOR_EQUAL): self
    {
        return $this->merge(new ExpressionClause($column, $op, $value), false);
    }

    public function or(Criteria $criteria): self
    {
        if ($criteria->getConditions()) {
            $this->merge($criteria->getConditions(), false);
        }

        return $this;
    }

    public function and(Criteria $criteria): self
    {
        if ($criteria->getConditions()) {
            $this->merge($criteria->getConditions());
        }

        return $this;
    }

    public function not(Criteria $criteria): self
    {
        if ($criteria->conditions) {
            $this->merge(new NotClause($criteria->getConditions()));
        }

        return $this;
    }

    private function merge(CriteriaClauseInterface $clause, $and = true): self
    {
        if ($this->conditions) {
            $this->conditions = $and ? new AndClause($this->conditions, $clause)
                : new OrClause($this->conditions, $clause);
        } else {
            $this->conditions = $clause;
        }

        return $this;
    }

    /**
     * @param Sort[] $columns
     *
     * @return static
     */
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
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getConditions(): ?CriteriaClauseInterface
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

    public function getGroupBy(): array
    {
        return $this->groupBy;
    }

    public function getBindValues(): array
    {
        return $this->bindValues;
    }

    public function getQuery(): array
    {
        if (empty($this->conditions)) {
            return ['1=1'];
        }

        return $this->buildQuery($this->conditions);
    }

    /**
     * 过滤查询条件
     * $callback 接受三个参数 function($column, $value, $operator), 也必须返回这三个参数构成的数组。
     *
     * @param ExpressionClauseFilterInterface|callable $filter the callback
     *
     * @return Criteria
     */
    public function filter($filter): self
    {
        $copy = clone $this;
        if ($copy->conditions) {
            $copy->conditions = $this->filterConditions($copy->conditions, $filter);
        }

        return $copy;
    }

    public function alias(array $columnAlias): self
    {
        $copy = clone $this;

        if ($this->columns) {
            $copy->columns = array_map(static function (string $column) use ($columnAlias) {
                return $columnAlias[$column] ?? $column;
            }, $this->columns);
        }

        if ($this->groupBy) {
            $copy->groupBy = array_map(static function (string $column) use ($columnAlias) {
                return $columnAlias[$column] ?? $column;
            }, $this->groupBy);
        }

        if ($this->orderBy) {
            $copy->orderBy = array_map(static function (Sort $sort) use ($columnAlias) {
                return isset($columnAlias[$sort->getColumn()])
                    ? Sort::of($columnAlias[$sort->getColumn()], $sort->getDirection())
                    : $sort;
            }, $this->orderBy);
        }

        if ($this->bindValues) {
            $copy->bindValues = [];
            foreach ($this->bindValues as $name => $value) {
                $copy->bindValues[$columnAlias[$name] ?? $name] = $value;
            }
        }

        return $copy;
    }

    public function buildStatement(StatementInterface $stmt): StatementInterface
    {
        $stmt->where(...$this->getQuery());
        if ($this->columns) {
            $stmt->select($this->getColumns());
        }
        if ($this->limit) {
            $stmt->limit($this->getLimit())->offset($this->getOffset());
        }
        if ($this->orderBy) {
            $stmt->orderBy(array_map(static function (Sort $sort) {
                return (string) $sort;
            }, $this->getOrderBy()));
        }
        if ($this->groupBy) {
            $stmt->groupBy($this->getGroupBy());
        }

        return $stmt;
    }

    private function buildQuery(CriteriaClauseInterface $conditions): array
    {
        if ($conditions instanceof LogicClause) {
            $left = $this->buildQuery($conditions->getLeft());
            $right = $this->buildQuery($conditions->getLeft());
            $stmtLeft = array_shift($left);
            $stmtRight = array_shift($right);
            $bindValues = array_merge($left, $right);
            $op = $conditions instanceof AndClause ? 'AND' : 'OR';
            $stmt = sprintf('(%s) %s (%s)', $stmtLeft, $op, $stmtRight);
            array_unshift($bindValues, $stmt);

            return $bindValues;
        }
        if ($conditions instanceof ExpressionClause) {
            $column = $columnAlias[$conditions->getColumn()] ?? $conditions->getColumn();
            if (is_array($conditions->getValue())) {
                if (!in_array($conditions->getOperator(), [self::OPERATOR_IN, self::OPERATOR_NOT_IN], true)) {
                    throw new \InvalidArgumentException($conditions->getOperator().' does not support array value');
                }
                $stmt = sprintf('%s %s (%s)',
                    $column,
                    self::OPERATOR_NOT_IN === $conditions->getOperator() ? self::OPERATOR_NOT_IN : self::OPERATOR_IN,
                    implode(',', array_fill(0, count($conditions->getValue()), '?')));

                return array_merge([$stmt], $conditions->getValue());
            }

            $stmt = sprintf('%s %s ?', $column, $conditions->getOperator());

            return [$stmt, $conditions->getValue()];
        }
        if ($conditions instanceof NotClause) {
            $stmt = $this->buildQuery($conditions->getClause());

            return [sprintf('!(%s)', $stmt[0]), $stmt[1]];
        }
        if ($conditions instanceof RawClause) {
            return array_merge([$conditions->getExpression()], $conditions->getBindValues());
        }
        throw new \InvalidArgumentException('unknown conditions type '.get_class($conditions));
    }

    private function filterConditions(CriteriaClauseInterface $conditions, $callback): CriteriaClauseInterface
    {
        if ($conditions instanceof LogicClause) {
            $class = get_class($conditions);

            return new $class($this->filterConditions($conditions->getLeft(), $callback),
                $this->filterConditions($conditions->getRight(), $callback));
        }

        if ($conditions instanceof ExpressionClause) {
            if ($callback instanceof ExpressionClauseFilterInterface) {
                return $callback->filter($conditions);
            }

            $ret = $callback($conditions);
            if ($ret instanceof CriteriaClauseInterface) {
                return $ret;
            }
            throw new \InvalidArgumentException('invalid filter callback return value');
        }

        if ($conditions instanceof NotClause) {
            return new NotClause($this->filterConditions($conditions->getClause(), $callback));
        }

        if ($conditions instanceof RawClause) {
            return $conditions;
        }
        throw new \InvalidArgumentException('unknown conditions type '.get_class($conditions));
    }
}
