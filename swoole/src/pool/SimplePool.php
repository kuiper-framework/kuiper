<?php

declare(strict_types=1);

namespace kuiper\swoole\pool;

use kuiper\swoole\coroutine\Channel;
use kuiper\swoole\coroutine\ChannelInterface;
use kuiper\swoole\coroutine\Coroutine;
use kuiper\swoole\exception\PoolTimeoutException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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
     * @var array
     */
    private $connections;

    private $currentConnections = 0;

    private static $CONNECTION_ID = 1;

    /**
     * Pool constructor.
     */
    public function __construct(string $poolName, callable $connectionFactory, PoolConfig $config, LoggerInterface $logger = null)
    {
        $this->poolName = $poolName;
        $this->connectionFactory = $connectionFactory;
        $this->poolConfig = $config;
        $this->channel = new Channel($this->poolConfig->getMaxConnections());
        $this->setLogger($logger ?? new NullLogger());
    }

    public function take()
    {
        $coroutineId = Coroutine::getCoroutineId();
        if (isset($this->connections[$coroutineId])) {
            return $this->connections[$coroutineId][1];
        }

        $num = $this->channel->size();

        if (0 === $num && $this->currentConnections < $this->poolConfig->getMaxConnections()) {
            ++$this->currentConnections;
            try {
                $connection = $this->createConnection();

                return $this->deferReleaseConnection($coroutineId, $connection);
            } catch (\Exception $exception) {
                --$this->currentConnections;
                throw $exception;
            }
        }

        $connection = $this->channel->pop($this->poolConfig->getWaitTimeout());
        if (false === $connection) {
            throw new PoolTimeoutException($this);
        }

        return $this->deferReleaseConnection($coroutineId, $connection);
    }

    public function reset(): void
    {
        $coroutineId = Coroutine::getCoroutineId();
        if (isset($this->connections[$coroutineId])) {
            unset($this->connections[$coroutineId]);
            --$this->currentConnections;
        }
    }

    private function deferReleaseConnection($coroutineId, $connection)
    {
        $this->connections[$coroutineId] = $connection;
        Coroutine::defer(function () use ($coroutineId) {
            $connection = $this->connections[$coroutineId] ?? null;
            if (isset($connection)) {
                $this->logger->debug(self::TAG."release connection {$this->poolName}#{$connection[0]}");
                $this->channel->push($connection);
                unset($this->connections[$coroutineId]);
            }
        });
        $this->logger->debug(self::TAG."obtain connection {$this->poolName}#{$connection[0]}");

        return $connection[1];
    }

    public function getName(): string
    {
        return $this->poolName;
    }

    private function createConnection()
    {
        $connectionId = self::$CONNECTION_ID++;
        $this->logger->info(self::TAG.sprintf('create connection %s#%d', $this->poolName, $connectionId));

        return [$connectionId, call_user_func($this->connectionFactory, $connectionId)];
    }
}
