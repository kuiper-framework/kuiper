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

/**
 * Interface StatementInterface.
 *
 * @method $this  leftJoin(string $table, string $cond)
 * @method $this  bindValues(array $bindValues)
 * @method $this  set(string $column, string $expression)
 * @method string getStatement()
 * @method array  getBindValues()
 * @method $this  union()
 * @method $this  unionAll()
 */
interface StatementInterface
{
    /**
     * Sets table alias.
     *
     * @return static
     */
    public function tableAlias(string $alias);

    /**
     * Sets selected columns.
     *
     * @return static
     */
    public function select(...$columns);

    /**
     * Sets where statement.
     *
     * @return static
     */
    public function where(mixed $condition, ...$args);

    /**
     * Sets the binding parameter.
     *
     * @return static
     */
    public function bindValue(string $name, mixed $value);

    /**
     * @param string|array|mixed $condition
     * @param array              $args
     *
     * @return static
     */
    public function orWhere(mixed $condition, ...$args);

    /**
     * Sets like statement.
     *
     * @return static
     */
    public function like(string $column, string $value);

    /**
     * Sets in statement.
     *
     * @return static
     */
    public function in(string $column, array $values);

    /**
     * @return static
     */
    public function orIn(string $column, array $values);

    /**
     * @return static
     */
    public function notIn(string $column, array $values);

    /**
     * @return static
     */
    public function orNotIn(string $column, array $values);

    /**
     * @return static
     */
    public function limit(int $limit);

    /**
     * @return static
     */
    public function offset(int $offset);

    /**
     * @return static
     */
    public function orderBy(array $orderSpec);

    /**
     * @return static
     */
    public function groupBy(array $columns);

    /**
     * @return static
     */
    public function cols(array $values);

    /**
     * @return static
     */
    public function addRow(array $values = []);

    /**
     * Executes statement.
     */
    public function execute(): bool;

    /**
     * Executes query statement.
     *
     * @return static
     */
    public function query();

    /**
     * Gets affected rows.
     */
    public function rowCount(): int;

    /**
     * close statement.
     */
    public function close(): void;

    /**
     * Gets the db connection.
     */
    public function withConnection(callable $call): void;

    /**
     * @return string[]|false
     */
    public function fetch(int $fetchStyle = null): mixed;

    /**
     * @return string|false
     */
    public function fetchColumn(int $columnNumber = 0): mixed;

    /**
     * @param int|null $fetchStyle
     *
     * @return array
     */
    public function fetchAll(int $fetchStyle = null): array;
}
