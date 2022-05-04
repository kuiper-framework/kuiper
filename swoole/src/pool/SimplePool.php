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

use kuiper\logger\Logger;
use kuiper\swoole\coroutine\Channel;
use kuiper\swoole\coroutine\ChannelInterface;
use kuiper\swoole\coroutine\Coroutine;
use kuiper\swoole\exception\PoolClosedException;
use kuiper\swoole\exception\PoolTimeoutException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class SimplePool implements PoolInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var callable
     */
    private $connectionFactory;

    private readonly ChannelInterface $channel;

    /**
     * @var ConnectionInterface[]
     */
    private array $connections = [];

    /**
     * @var ConnectionInterface[][]
     */
    private array $coroutineConnections;

    /**
     * Pool constructor.
     */
    public function __construct(
        private readonly string $poolName,
        callable $connectionFactory,
        private readonly PoolConfig $poolConfig,
        private readonly EventDispatcherInterface $eventDispatcher)
    {
        $this->connectionFactory = $connectionFactory;
        $this->channel = new Channel($this->poolConfig->getMaxConnections());
        $this->setLogger(Logger::nullLogger());
    }

    public function close(): void
    {
        unset($this->connectionFactory, $this->connections, $this->channel);
    }

    /**
     * {@inheritDoc}
     */
    public function take(): mixed
    {
        if (!isset($this->connectionFactory)) {
            throw new PoolClosedException();
        }

        $num = $this->channel->size();

        if (0 === $num && count($this->connections) < $this->poolConfig->getMaxConnections()) {
            return $this->deferReleaseConnection($this->createConnection());
        }

        /** @var ConnectionInterface|false $connection */
        $connection = $this->channel->pop($this->poolConfig->getWaitTimeout());
        if (false === $connection) {
            throw new PoolTimeoutException($this);
        }
        if ($this->poolConfig->getAgedTimeout() > 0
            && $connection->getCreatedAt() + $this->poolConfig->getAgedTimeout() < time()) {
            $connection->close();
            $connection = $this->createConnection($connection->getId());
        }

        return $this->deferReleaseConnection($connection);
    }

    /**
     * {@inheritDoc}
     */
    public function release(mixed $connection): void
    {
        $coroutineId = $this->getCoroutineId();
        if (isset($this->coroutineConnections[$coroutineId])) {
            foreach ($this->coroutineConnections[$coroutineId] as $i => $conn) {
                if ($conn->getResource() === $connection) {
                    unset($this->coroutineConnections[$coroutineId][$i]);
                    $this->channel->push($conn);

                    return;
                }
            }
        }
    }

    public function getConnections(): array
    {
        $list = [];
        foreach ($this->connections as $conn) {
            $list[] = $conn->getResource();
        }

        return $list;
    }

    private function deferReleaseConnection(ConnectionInterface $connection): mixed
    {
        $coroutineId = $this->getCoroutineId();
        if (!isset($this->coroutineConnections[$coroutineId])) {
            Coroutine::defer(function () use ($coroutineId): void {
                if (!empty($this->coroutineConnections[$coroutineId])) {
                    foreach ($this->coroutineConnections[$coroutineId] as $conn) {
                        $this->channel->push($conn);
                    }
                }
                unset($this->coroutineConnections[$coroutineId]);
            });
        }
        $this->coroutineConnections[$coroutineId][] = $connection;

        return $connection->getResource();
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->poolName;
    }

    private function createConnection(int $id = null): Connection
    {
        if (!isset($id)) {
            $id = count($this->connections);
        }
        $resource = null;
        // $this->logger->debug(self::TAG . sprintf('create connection %s#%d', $this->poolName, $id));
        try {
            $ret = call_user_func_array($this->connectionFactory, [$id, &$resource]);
            if (!isset($resource)) {
                $resource = $ret;
            }
            $connection = new Connection($id, $resource);
            $this->connections[$id] = $connection;
            $this->eventDispatcher->dispatch(new ConnectionCreateEvent($this->poolName, $connection));

            return $connection;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function getCoroutineId(): int
    {
        return Coroutine::isEnabled() ? Coroutine::getCoroutineId() : (int) getmypid();
    }
}
