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

use PDO;
use PDOStatement;

/**
 * An interface to the native PDO object.
 */
interface PdoInterface
{
    /**
     * Begins a transaction and turns off autocommit mode.
     *
     * @return bool true on success, false on failure
     *
     * @see http://php.net/manual/en/pdo.begintransaction.php
     */
    public function beginTransaction(): bool;

    /**
     * Commits the existing transaction and restores autocommit mode.
     *
     * @return bool true on success, false on failure
     *
     * @see http://php.net/manual/en/pdo.commit.php
     */
    public function commit(): bool;

    /**
     * Rolls back the current transaction and restores autocommit mode.
     *
     * @return bool true on success, false on failure
     *
     * @see http://php.net/manual/en/pdo.rollback.php
     */
    public function rollBack(): bool;

    /**
     * Gets the most recent error code.
     */
    public function errorCode(): string;

    /**
     * Gets the most recent error info.
     */
    public function errorInfo(): array;

    /**
     * Executes an SQL statement and returns the number of affected rows.
     *
     * @param string $statement the SQL statement to execute
     *
     * @return int the number of rows affected
     *
     * @see http://php.net/manual/en/pdo.exec.php
     */
    public function exec($statement): int;

    /**
     * Is a transaction currently active?
     *
     * @see http://php.net/manual/en/pdo.intransaction.php
     */
    public function inTransaction(): bool;

    /**
     * Returns the last inserted autoincrement sequence value.
     *
     * @param string|null $name the name of the sequence to check; typically needed
     *                          only for PostgreSQL, where it takes the form of `<table>_<column>_seq`
     *
     * @see http://php.net/manual/en/pdo.lastinsertid.php
     */
    public function lastInsertId(?string $name = null): string|false;

    /**
     * Prepares an SQL statement for execution.
     *
     * @param string $query   the SQL statement to prepare for execution
     * @param array  $options set these attributes on the returned
     *                        PDOStatement
     *
     * @see http://php.net/manual/en/pdo.prepare.php
     *
     * @return PDOStatement|false
     */
    public function prepare(string $query, array $options = []): PDOStatement|false;

    /**
     * Queries the database and returns a PDOStatement.
     *
     * @param string $statement          the SQL statement to prepare and execute
     * @param int    $mode
     * @param mixed  ...$fetch_mode_args
     *
     * @return PDOStatement|false
     *
     * @see http://php.net/manual/en/pdo.query.php
     */
    public function query($statement, $mode = PDO::ATTR_DEFAULT_FETCH_MODE, ...$fetch_mode_args): PDOStatement|false;

    /**
     * Quotes a value for use in an SQL statement.
     *
     * @param mixed $value          the value to quote
     * @param int   $parameter_type a data type hint for the database driver
     *
     * @return string the quoted value
     *
     * @see http://php.net/manual/en/pdo.quote.php
     */
    public function quote($value, $parameter_type = PDO::PARAM_STR): string;

    /**
     * Sets a PDO attribute value.
     *
     * @param mixed $attribute the PDO::ATTR_* constant
     * @param mixed $value     the value for the attribute
     *
     * @return bool True on success, false on failure. Note that if PDO has not
     *              not connected, all calls will be treated as successful.
     */
    public function setAttribute($attribute, $value): bool;

    /**
     * Gets a PDO attribute value.
     *
     * @param int $attribute the PDO::ATTR_* constant
     *
     * @return mixed the value for the attribute
     */
    public function getAttribute(int $attribute): mixed;

    /**
     * Returns all currently available PDO drivers.
     */
    public static function getAvailableDrivers(): array;
}
