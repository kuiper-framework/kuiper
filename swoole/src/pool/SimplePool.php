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

namespace kuiper\swoole\pool;

use kuiper\swoole\coroutine\Channel;
use kuiper\swoole\coroutine\ChannelInterface;
use kuiper\swoole\coroutine\Coroutine;
use kuiper\swoole\exception\PoolClosedException;
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
     * @var ConnectionInterface[][]
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

    public function close(): void
    {
        unset($this->connectionFactory, $this->connections, $this->channel);
    }

    /**
     * {@inheritDoc}
     */
    public function take()
    {
        if (!isset($this->connectionFactory)) {
            throw new PoolClosedException();
        }

        $num = $this->channel->size();

        if (0 === $num && $this->currentConnections < $this->poolConfig->getMaxConnections()) {
            ++$this->currentConnections;
            try {
                return $this->deferReleaseConnection($this->createConnection());
            } catch (\Exception $exception) {
                --$this->currentConnections;
                throw $exception;
            }
        }

        /** @var ConnectionInterface|false $connection */
        $connection = $this->channel->pop($this->poolConfig->getWaitTimeout());
        if (false === $connection) {
            throw new PoolTimeoutException($this);
        }
        if ($this->poolConfig->getAgedTimeout() > 0
            && $connection->getCreatedAt() + $this->poolConfig->getAgedTimeout() < time()) {
            $connection->close();
            $connection = $this->createConnection();
        }

        return $this->deferReleaseConnection($connection);
    }

    /**
     * {@inheritDoc}
     */
    public function release($connection): void
    {
        $coroutineId = $this->getCoroutineId();
        foreach ($this->connections[$coroutineId] as $i => $conn) {
            if ($conn->getResource() === $connection) {
                unset($this->connections[$coroutineId][$i]);
                $this->channel->push($conn);

                return;
            }
        }
    }

    public function getConnections(): array
    {
        $list = [];
        foreach ($this->connections as $coroutineConns) {
            foreach ($coroutineConns as $conn) {
                $list[] = $conn->getResource();
            }
        }

        return $list;
    }

    private function deferReleaseConnection(ConnectionInterface $connection)
    {
        $coroutineId = $this->getCoroutineId();
        if (!isset($this->connections[$coroutineId])) {
            Coroutine::defer(function () use ($coroutineId): void {
                if (!empty($this->connections[$coroutineId])) {
                    foreach ($this->connections[$coroutineId] as $conn) {
                        $this->channel->push($conn);
                    }
                }
                unset($this->connections[$coroutineId]);
            });
        }
        $this->connections[$coroutineId][] = $connection;

        return $connection->getResource();
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->poolName;
    }

    private function createConnection(): Connection
    {
        $id = self::$CONNECTION_ID++;
        $resource = null;
        $this->logger->debug(self::TAG.sprintf('create connection %s#%d', $this->poolName, $id));
        $ret = call_user_func_array($this->connectionFactory, [$id, &$resource]);
        if (!isset($resource)) {
            $resource = $ret;
        }
        $connection = new Connection($id, $resource);
        $this->eventDispatcher->dispatch(new ConnectionCreateEvent($this->poolName, $connection));

        return $connection;
    }

    private function getCoroutineId(): int
    {
        return Coroutine::isEnabled() ? Coroutine::getCoroutineId() : (int) getmypid();
    }
}
