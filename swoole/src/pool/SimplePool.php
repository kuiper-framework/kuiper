<?php

declare(strict_types=1);

namespace kuiper\swoole\pool;

use kuiper\helper\Arrays;
use kuiper\swoole\coroutine\Channel;
use kuiper\swoole\coroutine\ChannelInterface;
use kuiper\swoole\coroutine\Coroutine;
use kuiper\swoole\exception\PoolTimeoutException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class SimplePool implements PoolInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var string
     */
    private $poolName;
    /**
     * @var callable
     */
    private $connectionFactory;
    /**
     * @var PoolConfig
     */
    private $poolConfig;
    /**
     * @var ChannelInterface
     */
    private $channel;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var Connection[]
     */
    private $connections;

    /**
     * @var int
     */
    private $currentConnections = 0;

    /**
     * @var int
     */
    private static $CONNECTION_ID = 1;

    /**
     * Pool constructor.
     */
    public function __construct(string $poolName, callable $connectionFactory, PoolConfig $config, EventDispatcherInterface $eventDispatcher, LoggerInterface $logger)
    {
        $this->poolName = $poolName;
        $this->connectionFactory = $connectionFactory;
        $this->poolConfig = $config;
        $this->channel = new Channel($this->poolConfig->getMaxConnections());
        $this->eventDispatcher = $eventDispatcher;
        $this->setLogger($logger);
    }

    public function take()
    {
        $coroutineId = $this->getCoroutineId();
        if (isset($this->connections[$coroutineId]->conn)) {
            return $this->connections[$coroutineId]->conn;
        }

        $num = $this->channel->size();

        if (0 === $num && $this->currentConnections < $this->poolConfig->getMaxConnections()) {
            ++$this->currentConnections;
            try {
                return $this->deferReleaseConnection($coroutineId, $this->createConnection());
            } catch (\Exception $exception) {
                --$this->currentConnections;
                throw $exception;
            }
        }

        $connection = $this->channel->pop($this->poolConfig->getWaitTimeout());
        if (false === $connection) {
            throw new PoolTimeoutException($this);
        }
        if (!isset($connection->conn)) {
            $connection = $this->createConnection();
        }

        return $this->deferReleaseConnection($coroutineId, $connection);
    }

    public function getConnections(): array
    {
        return Arrays::pullField($this->connections, 'conn');
    }

    public function reset(): void
    {
        $coroutineId = $this->getCoroutineId();
        if (isset($this->connections[$coroutineId])) {
            unset($this->connections[$coroutineId]);
            --$this->currentConnections;
        }
    }

    /**
     * @param Connection $connection
     *
     * @return mixed
     */
    private function deferReleaseConnection(int $coroutineId, Connection $connection)
    {
        $this->connections[$coroutineId] = $connection;
        Coroutine::defer(function () use ($coroutineId): void {
            $connection = $this->connections[$coroutineId] ?? null;
            if (isset($connection, $connection->conn)) {
                $this->logger->debug(self::TAG."release connection {$this->poolName}#{$connection->id}");
                $this->channel->push($connection);
                $this->eventDispatcher->dispatch(new ConnectionReleaseEvent($this->poolName, $connection));
                unset($this->connections[$coroutineId]);
            }
        });
        $this->logger->debug(self::TAG."obtain connection {$this->poolName}#{$connection->id}");

        return $connection->conn;
    }

    public function getName(): string
    {
        return $this->poolName;
    }

    private function createConnection(): Connection
    {
        $connection = new Connection(self::$CONNECTION_ID++);
        $this->logger->info(self::TAG.sprintf('create connection %s#%d', $this->poolName, $connection->id));
        $ret = call_user_func_array($this->connectionFactory, [$connection->id, &$connection->conn]);
        $this->eventDispatcher->dispatch(new ConnectionCreateEvent($this->poolName, $connection));
        if (!isset($connection->conn)) {
            $connection->conn = $ret;
        }

        return $connection;
    }

    private function getCoroutineId(): int
    {
        return Coroutine::isEnabled() ? Coroutine::getCoroutineId() : (int) getmypid();
    }
}
