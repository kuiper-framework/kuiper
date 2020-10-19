<?php

declare(strict_types=1);

namespace kuiper\db;

interface StatementInterface
{
    /**
     * Sets table alias.
     */
    public function tableAlias(string $alias): StatementInterface;

    /**
     * @param array $columns
     *
     * @return static
     */
    public function select(...$columns): StatementInterface;

    /**
     * @param string|array $condition
     * @param array        $args
     *
     * @return static
     */
    public function where($condition, ...$args): StatementInterface;

    /**
     * @param mixed $value
     */
    public function bindValue(string $name, $value): StatementInterface;

    /**
     * @param string|array $condition
     * @param array        $args
     *
     * @return static
     */
    public function orWhere($condition, ...$args): StatementInterface;

    /**
     * @return static
     */
    public function like(string $column, string $value): StatementInterface;

    /**
     * @return static
     */
    public function in(string $column, array $values): StatementInterface;

    /**
     * @return static
     */
    public function orIn(string $column, array $values): StatementInterface;

    /**
     * @return static
     */
    public function notIn(string $column, array $values): StatementInterface;

    /**
     * @return static
     */
    public function orNotIn(string $column, array $values): StatementInterface;

    /**
     * @return static
     */
    public function limit(int $limit): StatementInterface;

    /**
     * @return static
     */
    public function offset(int $offset): StatementInterface;

    /**
     * @return static
     */
    public function orderBy(array $orderSpec): StatementInterface;

    /**
     * @return static
     */
    public function groupBy(array $columns): StatementInterface;

    /**
     * @return static
     */
    public function cols(array $values): StatementInterface;

    /**
     * @return static
     */
    public function addRow(array $values): StatementInterface;

    /**
     * Executes statement.
     */
    public function execute(): bool;

    /**
     * Executes query statement.
     */
    public function query(): StatementInterface;

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
    public function getConnection(): ?ConnectionInterface;

    public function fetch(int $fetchStyle = null);

    public function fetchColumn(int $columnNumber = 0);

    public function fetchAll(int $fetchStyle = null);
}
