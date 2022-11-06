<?php

/** @noinspection PhpUnused */

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

use InvalidArgumentException;
use kuiper\db\criteria\AndClause;
use kuiper\db\criteria\CriteriaClauseInterface;
use kuiper\db\criteria\CriteriaFilterInterface;
use kuiper\db\criteria\ExpressionClause;
use kuiper\db\criteria\LogicClause;
use kuiper\db\criteria\NotClause;
use kuiper\db\criteria\OrClause;
use kuiper\db\criteria\RawClause;
use kuiper\db\criteria\Sort;
use kuiper\helper\Arrays;
use Webmozart\Assert\Assert;

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
    private array $columns = [];

    private ?CriteriaClauseInterface $clause = null;

    private ?int $limit = null;

    private ?int $offset = null;

    private array $orderBy = [];

    private array $groupBy = [];

    private array $bindValues = [];

    final private function __construct()
    {
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
     * @param string ...$columns
     */
    public function select(...$columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    public function limit(int $limit, ?int $offset = null): self
    {
        Assert::greaterThan($limit, 0, "limit should greater then 0, got $limit");
        $this->limit = $limit;
        if (isset($offset)) {
            $this->offset = $offset;
        }

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function where(string $column, mixed $value, string $op = self::OPERATOR_EQUAL): self
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

    public function like(string $column, mixed $value): self
    {
        return $this->where($column, $value, self::OPERATOR_LIKE);
    }

    public function notLike(string $column, mixed $value): self
    {
        return $this->where($column, $value, self::OPERATOR_NOT_LIKE);
    }

    public function in(string $column, array $value): self
    {
        if (empty($value)) {
            throw new InvalidArgumentException('value expected not empty');
        }

        return $this->where($column, $value, self::OPERATOR_IN);
    }

    public function notIn(string $column, array $value): self
    {
        if (empty($value)) {
            throw new InvalidArgumentException('value expected not empty');
        }

        return $this->where($column, $value, self::OPERATOR_NOT_IN);
    }

    public function orWhere(string $column, mixed $value, string $op = self::OPERATOR_EQUAL): self
    {
        return $this->merge(new ExpressionClause($column, $op, $value), false);
    }

    public function or(Criteria $criteria): self
    {
        if (null !== $criteria->getClause()) {
            $this->merge($criteria->getClause(), false);
        }

        return $this;
    }

    public function and(Criteria $criteria): self
    {
        if (null !== $criteria->getClause()) {
            $this->merge($criteria->getClause());
        }

        return $this;
    }

    public function not(Criteria $criteria): self
    {
        if (null !== $criteria->clause) {
            $this->merge(new NotClause($criteria->getClause()));
        }

        return $this;
    }

    public function matches(array $naturalIds, array $columns): self
    {
        if (empty($naturalIds) || empty($columns)) {
            return $this;
        }
        if (1 === count($columns)) {
            return $this->in($columns[0], Arrays::pull($naturalIds, $columns[0]));
        }
        foreach ($columns as $column) {
            foreach ($naturalIds as $naturalId) {
                $value = $naturalId[$column] ?? null;
                if (!isset($value)) {
                    throw new InvalidArgumentException("$column is required");
                }
                if (!is_scalar($value)) {
                    throw new InvalidArgumentException("Support only scalar type, $column is ".get_debug_type($value));
                }
            }
        }

        return $this->matchesInternal($naturalIds, $columns);
    }

    private function matchesInternal(array $naturalIds, array $columns): self
    {
        $groupCounts = [];
        foreach ($columns as $column) {
            $values = [];
            foreach ($naturalIds as $naturalId) {
                $values[$naturalId[$column]] = true;
            }
            $groupCounts[$column] = count($values);
        }
        asort($groupCounts);
        $columns = array_keys($groupCounts);
        $column = array_shift($columns);
        $match = self::create();
        foreach (Arrays::groupBy($naturalIds, $column) as $columnValue => $group) {
            // $columnValue type may change to int, should use original value
            $criteria = self::create([$column => $group[0][$column]]);
            if (1 === count($columns)) {
                if (1 === count($group)) {
                    $criteria->where($columns[0], $group[0][$columns[0]]);
                } else {
                    $criteria->in($columns[0], Arrays::pull($group, $columns[0]));
                }
            } else {
                $criteria->and(self::create()->matchesInternal($group, $columns));
            }
            $match->merge($criteria->getClause(), false);
        }

        return $this->merge($match->getClause());
    }

    private function merge(CriteriaClauseInterface $clause, bool $and = true): self
    {
        if (null !== $this->clause) {
            $this->clause = $and ? new AndClause($this->clause, $clause)
                : new OrClause($this->clause, $clause);
        } else {
            $this->clause = $clause;
        }

        return $this;
    }

    /**
     * @param Sort[] $columns
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

    public function getClause(): ?CriteriaClauseInterface
    {
        return $this->clause;
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
        if (null === $this->clause) {
            return ['1=1'];
        }

        return $this->buildQuery($this->clause);
    }

    /**
     * 过滤查询条件
     * $callback 接受三个参数 function($column, $value, $operator), 也必须返回这三个参数构成的数组。
     *
     * @param CriteriaFilterInterface|callable $filter the callback
     *
     * @return Criteria
     */
    public function filter(CriteriaFilterInterface|callable $filter): self
    {
        $copy = clone $this;
        if (null !== $copy->clause) {
            $copy->clause = $this->filterClause($copy->clause, $filter);
        }

        return $copy;
    }

    public function alias(array $columnAlias): self
    {
        $copy = clone $this;

        if (!empty($this->columns)) {
            $copy->columns = array_map(static function (string $column) use ($columnAlias) {
                return $columnAlias[$column] ?? $column;
            }, $this->columns);
        }

        if (!empty($this->groupBy)) {
            $copy->groupBy = array_map(static function (string $column) use ($columnAlias) {
                return $columnAlias[$column] ?? $column;
            }, $this->groupBy);
        }

        if (!empty($this->orderBy)) {
            $copy->orderBy = array_map(static function (Sort $sort) use ($columnAlias): Sort {
                return isset($columnAlias[$sort->getColumn()])
                    ? Sort::of($columnAlias[$sort->getColumn()], $sort->getDirection())
                    : $sort;
            }, $this->orderBy);
        }

        if (!empty($this->bindValues)) {
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
        if (!empty($this->columns)) {
            $stmt->select(...$this->getColumns());
        }
        if (isset($this->limit)) {
            $stmt->limit($this->getLimit());
            if (isset($this->offset)) {
                $stmt->offset($this->getOffset());
            }
        }
        if (!empty($this->orderBy)) {
            $stmt->orderBy(array_map(static function (Sort $sort): string {
                return (string) $sort;
            }, $this->getOrderBy()));
        }
        if (!empty($this->groupBy)) {
            $stmt->groupBy($this->getGroupBy());
        }

        return $stmt;
    }

    private function buildQuery(CriteriaClauseInterface $clause): array
    {
        if ($clause instanceof LogicClause) {
            $left = $this->buildQuery($clause->getLeft());
            $right = $this->buildQuery($clause->getRight());
            $stmtLeft = array_shift($left);
            $stmtRight = array_shift($right);
            $bindValues = array_merge($left, $right);
            $op = $clause instanceof AndClause ? 'AND' : 'OR';
            $stmt = sprintf('(%s) %s (%s)', $stmtLeft, $op, $stmtRight);
            array_unshift($bindValues, $stmt);

            return $bindValues;
        }
        if ($clause instanceof ExpressionClause) {
            $column = $clause->getColumn();
            if (is_array($clause->getValue())) {
                if (!in_array($clause->getOperator(), [self::OPERATOR_IN, self::OPERATOR_NOT_IN], true)) {
                    throw new InvalidArgumentException($clause->getOperator().' does not support array value');
                }
                $stmt = sprintf('%s %s (%s)',
                    $column,
                    self::OPERATOR_NOT_IN === $clause->getOperator() ? self::OPERATOR_NOT_IN : self::OPERATOR_IN,
                    implode(',', array_fill(0, count($clause->getValue()), '?')));

                return array_merge([$stmt], $clause->getValue());
            }

            $stmt = sprintf('%s %s ?', $column, $clause->getOperator());

            return [$stmt, $clause->getValue()];
        }
        if ($clause instanceof NotClause) {
            $stmt = $this->buildQuery($clause->getClause());

            return [sprintf('!(%s)', $stmt[0]), $stmt[1]];
        }
        if ($clause instanceof RawClause) {
            return array_merge([$clause->getExpression()], $clause->getBindValues());
        }
        throw new InvalidArgumentException('unknown conditions type '.get_class($clause));
    }

    private function filterClause(CriteriaClauseInterface $clause, CriteriaFilterInterface|callable $callback): CriteriaClauseInterface
    {
        if ($clause instanceof LogicClause) {
            $class = get_class($clause);

            return new $class($this->filterClause($clause->getLeft(), $callback),
                $this->filterClause($clause->getRight(), $callback));
        }

        if ($clause instanceof ExpressionClause) {
            if ($callback instanceof CriteriaFilterInterface) {
                return $callback->filter($clause);
            }

            $ret = $callback($clause);
            if ($ret instanceof CriteriaClauseInterface) {
                return $ret;
            }
            throw new InvalidArgumentException('invalid filter callback return value');
        }

        if ($clause instanceof NotClause) {
            return new NotClause($this->filterClause($clause->getClause(), $callback));
        }

        if ($clause instanceof RawClause) {
            return $clause;
        }
        throw new InvalidArgumentException('unknown conditions type '.get_class($clause));
    }
}
