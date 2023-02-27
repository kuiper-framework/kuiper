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

use BadMethodCallException;
use kuiper\db\constants\ErrorCode;
use kuiper\db\event\ConnectedEvent;
use kuiper\db\event\DisconnectedEvent;
use kuiper\db\event\SqlExecutedEvent;
use kuiper\db\event\SqlPreparedEvent;
use kuiper\db\event\SqlQueriedEvent;
use kuiper\db\event\TimedOutEvent;
use kuiper\event\EventDispatcherAwareInterface;
use kuiper\event\EventDispatcherAwareTrait;
use kuiper\event\NullEventDispatcher;
use PDO;
use PDOException;
use PDOStatement;
use Stringable;

class Connection extends PDO implements ConnectionInterface, EventDispatcherAwareInterface, Stringable
{
    use EventDispatcherAwareTrait;

    /**
     * @var int
     */
    private static int $GID = 1;

    /**
     * The attributes for a lazy connection.
     *
     * @var array
     */
    protected array $attributes = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];

    /**
     * The PDO connection itself.
     */
    protected ?PDO $pdo = null;

    protected bool $longRunning = true;

    protected int $timeout = 300;

    protected int $connectedAt = -1;

    protected bool $inTransaction = false;

    protected float $lastQueryStart = -1.0;

    private int $uniqueId = 0;

    /**
     * Constructor connection.
     *
     * @param string      $dsn        The data source name for a lazy PDO connection,
     * @param string|null $username   the username for a lazy connection
     * @param string|null $password   the password for a lazy connection
     * @param array       $options    driver-specific options for a lazy connection
     * @param array       $attributes attributes to set after a lazy connection
     *
     * @see http://php.net/manual/en/pdo.construct.php
     *
     * @noinspection MagicMethodsValidityInspection
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(
        private readonly string $dsn,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
        private readonly array $options = [],
        array $attributes = [])
    {
        $this->attributes = array_replace($this->attributes, $attributes);
        $this->setEventDispatcher(new NullEventDispatcher());
    }

    /**
     * {@inheritdoc}
     */
    public function connect(): void
    {
        // don't connect twice
        if (null !== $this->pdo) {
            if (!$this->isTimeout()) {
                return;
            }
            $this->dispatch(new TimedOutEvent($this));
            $this->disconnect();
        }

        $this->pdo = new PDO($this->dsn, $this->username, $this->password, $this->options);
        $this->uniqueId = self::$GID++;

        // set attributes
        foreach ($this->attributes as $attribute => $value) {
            $this->setAttribute($attribute, $value);
        }

        $this->connectedAt = time();
        $this->inTransaction = false;
        $this->dispatch(new ConnectedEvent($this));
    }

    /**
     * @return string
     */
    public function getDsn(): string
    {
        return $this->dsn;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function __toString(): string
    {
        return sprintf('PDO[%d] connected at %s', $this->uniqueId, date('Y-m-d H:i:s', $this->connectedAt));
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect(): void
    {
        $this->pdo = null;
        $this->dispatch(new DisconnectedEvent($this));
    }

    public function reconnect(): void
    {
        $this->disconnect();
        $this->connect();
    }

    /**
     * {@inheritdoc}
     */
    public function setAttribute($attribute, $value): bool
    {
        if (null !== $this->pdo) {
            return $this->pdo->setAttribute($attribute, $value);
        }

        $this->attributes[$attribute] = $value;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute(int $attribute): mixed
    {
        $this->connect();

        return $this->pdo->getAttribute($attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function getPdo(): PDO
    {
        $this->connect();

        return $this->pdo;
    }

    /**
     * {@inheritdoc}
     */
    public function errorCode(): string
    {
        $this->connect();

        return $this->pdo->errorCode();
    }

    /**
     * {@inheritdoc}
     */
    public function errorInfo(): array
    {
        $this->connect();

        return $this->pdo->errorInfo();
    }

    /**
     * {@inheritdoc}
     */
    public function quote($value, $parameter_type = PDO::PARAM_STR): string
    {
        $this->connect();

        // non-array quoting
        if (!is_array($value)) {
            return $this->pdo->quote((string) $value, $parameter_type);
        }

        // quote array values, not keys, then combine with commas
        foreach ($value as $k => $v) {
            $value[$k] = $this->pdo->quote((string) $v, $parameter_type);
        }

        return implode(', ', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function exec($statement): int
    {
        $this->connect();
        $this->beforeQuery();
        try {
            $affectedRows = @$this->pdo->exec($statement);
        } catch (PDOException $e) {
            if (ErrorCode::isRetryable($e)) {
                $this->reconnect();
                $affectedRows = $this->pdo->exec($statement);
            } else {
                throw $e;
            }
        }
        $this->dispatch(new SqlExecutedEvent($this, $statement));

        return (int) $affectedRows;
    }

    /**
     * {@inheritdoc}
     */
    public function query($statement, $mode = PDO::ATTR_DEFAULT_FETCH_MODE, ...$fetch_mode_args): PDOStatement|false
    {
        $this->connect();

        // remove empty constructor params list if it exists
        $args = func_get_args();
        if (4 === count($args) && [] === $args[3]) {
            unset($args[3]);
        }

        $this->beforeQuery();
        try {
            $sth = @call_user_func_array([$this->pdo, 'query'], $args);
        } catch (PDOException $e) {
            if (ErrorCode::isRetryable($e)) {
                $this->reconnect();
                $sth = call_user_func_array([$this->pdo, 'query'], $args);
            } else {
                throw $e;
            }
        }
        $this->dispatch(new SqlQueriedEvent($this, $statement, $args));

        return $sth;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->connect();
        $this->beforeQuery();
        $sth = $this->pdo->prepare($query, $options);
        $this->dispatch(new SqlPreparedEvent($this, $query));

        return $sth;
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId(?string $name = null): string|false
    {
        if (null === $this->pdo) {
            throw new BadMethodCallException('Cannot call lastInsertId without insert');
        }

        return $this->pdo->lastInsertId($name);
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(): bool
    {
        $this->connect();
        $result = $this->pdo->beginTransaction();
        $this->inTransaction = true;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function inTransaction(): bool
    {
        $this->connect();

        return $this->pdo->inTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        $this->connect();
        try {
            return $this->pdo->commit();
        } finally {
            $this->inTransaction = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack(): bool
    {
        $this->connect();
        try {
            return $this->pdo->rollBack();
        } finally {
            $this->inTransaction = false;
        }
    }

    public function setLongRunning(bool $longRunning = true): self
    {
        $this->longRunning = $longRunning;

        return $this;
    }

    public function isLongRunning(): bool
    {
        return $this->longRunning;
    }

    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getLastQueryStart(): ?float
    {
        return $this->lastQueryStart;
    }

    public function getLastQueryElapsed(): float
    {
        return $this->lastQueryStart > 0 ? microtime(true) - $this->lastQueryStart : 0;
    }

    protected function isTimeout(): bool
    {
        if (!$this->longRunning) {
            return false;
        }
        if ($this->inTransaction) {
            return false;
        }

        return time() - $this->connectedAt > $this->timeout;
    }

    protected function beforeQuery(): void
    {
        $this->lastQueryStart = microtime(true);
    }

    protected function dispatch(object $event): void
    {
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * {@inheritdoc}
     */
    public static function getAvailableDrivers(): array
    {
        return PDO::getAvailableDrivers();
    }

    public static function isRetryableError(PDOException $e): bool
    {
        return isset($e->errorInfo[1])
            && in_array($e->errorInfo[1], [ErrorCode::CR_SERVER_LOST, ErrorCode::CR_SERVER_GONE_ERROR], true);
    }
}
