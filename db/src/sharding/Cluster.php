<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

use Aura\SqlQuery\QueryFactory;
use kuiper\db\Connection;
use kuiper\db\ConnectionInterface;
use kuiper\db\Events;
use kuiper\helper\Arrays;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Webmozart\Assert\Assert;

class Cluster implements ClusterInterface
{
    /**
     * @var array
     */
    private $databases;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var array
     */
    private $options;

    /**
     * @var array
     */
    private $attributes;

    /**
     * @var bool
     */
    private $autoCreateTable;

    /**
     * @var string
     */
    private $driver;

    /**
     * @var Connection[]
     */
    private $connections;

    /**
     * @var array
     */
    private $tables;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    public function __construct(array $databases, $username = null, $password = null, $options = [], array $attributes = [], $driver = null, $autoCreateTable = false)
    {
        $this->setDatabases($databases);
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
        $this->attributes = $attributes;
        $this->driver = $driver;
        $this->autoCreateTable = $autoCreateTable;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection($id)
    {
        if (isset($this->connections[$id])) {
            return $this->connections[$id];
        }
        list($dsn, $username, $password) = $this->getDsn($id);
        $this->connections[$id] = $connection = new Connection($dsn, $username, $password, $this->options, $this->attributes);
        if ($this->eventDispatcher) {
            $connection->setEventDispatcher($this->eventDispatcher);
            $this->eventDispatcher->dispatch(Events::CLUSTER_CONNECTED, new GenericEvent($connection));
        }

        return $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function setTableStrategy($table, StrategyInterface $strategy)
    {
        $this->tables[$table] = $strategy;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTableStrategy($table)
    {
        return $this->tables[$table];
    }

    /**
     * {@inheritdoc}
     */
    public function from($table)
    {
        return $this->createStatement($table, $query = $this->getQueryFactory()->newSelect());
    }

    /**
     * {@inheritdoc}
     */
    public function delete($table)
    {
        return $this->createStatement($table, $query = $this->getQueryFactory()->newDelete());
    }

    /**
     * {@inheritdoc}
     */
    public function update($table)
    {
        return $this->createStatement($table, $query = $this->getQueryFactory()->newUpdate());
    }

    /**
     * {@inheritdoc}
     */
    public function insert($table)
    {
        return $this->createStatement($table, $query = $this->getQueryFactory()->newInsert());
    }

    public function getQueryFactory()
    {
        if (null === $this->queryFactory) {
            $this->queryFactory = new QueryFactory($this->getDriver());
        }

        return $this->queryFactory;
    }

    /**
     * @return $this
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * @param string $driver
     *
     * @return $this
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * @return $this
     */
    public function setAutoCreateTable(bool $autoCreateTable)
    {
        $this->autoCreateTable = $autoCreateTable;

        return $this;
    }

    public function getDriver()
    {
        if (null === $this->driver) {
            $keys = array_keys($this->databases);
            $id = $keys[0];
            if (isset($this->connections[$id])) {
                $connection = $this->connections[$id];
                $this->driver = $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
            } else {
                list($dsn) = $this->getDsn($keys[0]);
                $this->driver = substr($dsn, 0, $pos = strpos($dsn, ':'));
            }
        }

        return $this->driver;
    }

    protected function createStatement($table, $query)
    {
        Assert::keyExists($this->tables, $table, "Table '{$table}' strategy was not configured, call addTable first");

        return new Statement($this, $query, $table, $this->tables[$table], $this->autoCreateTable, $this->eventDispatcher);
    }

    protected function setDatabases(array $databases)
    {
        Assert::notEmpty($databases);
        foreach ($databases as $id => $config) {
            if (is_string($config)) {
                $databases[$id] = $config = ['dsn' => $config];
            } elseif (is_array($config)) {
                Assert::keyExists($config, 'dsn');
            } elseif ($config instanceof ConnectionInterface) {
                $this->connections[$id] = $config;
                continue;
            } else {
                throw new \InvalidArgumentException("Expected string or array for databases config item '{$id}'");
            }
            Assert::true(false !== strpos($config['dsn'], ':'), 'Invalid database dsn');
        }
        $this->databases = $databases;
    }

    protected function getDsn($id)
    {
        if (!isset($this->databases[$id])) {
            throw new \InvalidArgumentException("Connection id '{$id}' does not exist");
        }
        $config = $this->databases[$id];

        return [
            $config['dsn'],
            Arrays::fetch($config, 'username', $this->username),
            Arrays::fetch($config, 'password', $this->password),
        ];
    }
}
