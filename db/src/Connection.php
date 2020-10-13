<?php

declare(strict_types=1);

namespace kuiper\db;

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
use PDOStatement;

class Connection extends PDO implements ConnectionInterface, EventDispatcherAwareInterface
{
    use EventDispatcherAwareTrait;

    private static $GID = 1;

    /**
     * The attributes for a lazy connection.
     *
     * @var array
     */
    protected $attributes = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];

    /**
     * The DSN for a lazy connection.
     *
     * @var string
     */
    protected $dsn;

    /**
     * The username for a lazy connection.
     *
     * @var string
     */
    protected $username;

    /**
     * The password for a lazy connection.
     *
     * @var string
     */
    protected $password;

    /**
     * PDO options for a lazy connection.
     *
     * @var array
     */
    protected $options = [];

    /**
     * The PDO connection itself.
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var bool whether
     */
    protected $longRunning = true;

    /**
     * @var int
     */
    protected $timeout = 300;

    /**
     * @var int
     */
    protected $connectedAt = -1;

    /**
     * @var bool
     */
    protected $inTransaction = false;

    /**
     * @var float
     */
    protected $lastQueryStart;

    /**
     * @var int
     */
    private $uniqueId;

    /**
     * Constructor connection.
     *
     * @param string $dsn        The data source name for a lazy PDO connection,
     * @param string $username   the username for a lazy connection
     * @param string $password   the password for a lazy connection
     * @param array  $options    driver-specific options for a lazy connection
     * @param array  $attributes attributes to set after a lazy connection
     *
     * @see http://php.net/manual/en/pdo.construct.php
     * @noinspection MagicMethodsValidityInspection
     */
    public function __construct($dsn, $username = null, $password = null, array $options = [], array $attributes = [])
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
        $this->attributes = array_replace($this->attributes, $attributes);
        $this->setEventDispatcher(new NullEventDispatcher());
    }

    /**
     * {@inheritdoc}
     */
    public function connect(): void
    {
        // don't connect twice
        if ($this->pdo) {
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
        if ($this->pdo) {
            return $this->pdo->setAttribute($attribute, $value);
        }

        $this->attributes[$attribute] = $value;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($attribute)
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
            return $this->pdo->quote($value, $parameter_type);
        }

        // quote array values, not keys, then combine with commas
        foreach ($value as $k => $v) {
            $value[$k] = $this->pdo->quote($v, $parameter_type);
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
        } catch (\PDOException $e) {
            if (self::isRetryableError($e)) {
                $this->reconnect();
                $affectedRows = $this->pdo->exec($statement);
            } else {
                throw $e;
            }
        }
        $this->dispatch(new SqlExecutedEvent($this, $statement));

        return $affectedRows;
    }

    /**
     * {@inheritdoc}
     */
    public function query($statement, $fetch_mode = \PDO::ATTR_DEFAULT_FETCH_MODE, $fetch_arg1 = null, array $fetch_arg2 = []): PDOStatement
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
        } catch (\PDOException $e) {
            if (self::isRetryableError($e)) {
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
    public function prepare($statement, $options = [])
    {
        $this->connect();
        $this->beforeQuery();
        $sth = $this->pdo->prepare($statement, $options);
        $this->dispatch(new SqlPreparedEvent($this, $statement));

        return $sth;
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($name = null)
    {
        if (!$this->pdo) {
            throw new \BadMethodCallException('Cannot call lastInsertId without insert');
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

    public function setLongRunning($longRunning = true): self
    {
        $this->longRunning = $longRunning;

        return $this;
    }

    public function isLongRunning(): bool
    {
        return $this->longRunning;
    }

    public function setTimeout($timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getLastQueryStart(): float
    {
        return $this->lastQueryStart;
    }

    public function getLastQueryElapsed(): float
    {
        return $this->lastQueryStart ? microtime(true) - $this->lastQueryStart : 0;
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

    protected function beforeQuery()
    {
        $this->lastQueryStart = microtime(true);
    }

    protected function dispatch($event): void
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

    public static function isRetryableError(\PDOException $e): bool
    {
        return in_array($e->errorInfo[1], [ErrorCode::CR_SERVER_LOST, ErrorCode::CR_SERVER_GONE_ERROR], true);
    }
}
