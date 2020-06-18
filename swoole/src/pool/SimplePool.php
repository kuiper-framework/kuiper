<?php

declare(strict_types=1);

namespace kuiper\swoole\pool;

use kuiper\swoole\coroutine\Channel;
use kuiper\swoole\coroutine\ChannelInterface;
use kuiper\swoole\coroutine\Coroutine;
use kuiper\swoole\coroutine\SplQueueChannel;

class SimplePool implements PoolInterface
{
    use PoolTrait;

    /**
     * @var callable
     */
    private $connectionFactory;
    /**
     * @var PoolConfig
     */
    private $config;
    /**
     * @var ChannelInterface
     */
    private $channel;

    private $currentConnections = 0;

    /**
     * Pool constructor.
     */
    public function __construct(callable $connectionFactory, PoolConfig $config, ChannelInterface $channel = null)
    {
        $this->connectionFactory = $connectionFactory;
        $this->config = $config;
        $this->channel = $channel ?? (Coroutine::isEnabled() ? new Channel($config->getMaxConnections()) : new SplQueueChannel());
    }

    public function take()
    {
        $num = $this->channel->size();

        if (0 === $num && $this->currentConnections < $this->config->getMaxConnections()) {
            try {
                ++$this->currentConnections;

                return call_user_func($this->connectionFactory, $this->currentConnections);
            } catch (\Exception $exception) {
                --$this->currentConnections;
                throw $exception;
            }
        }

        return $this->channel->pop($this->config->getWaitTimeout());
    }

    public function release($connection): void
    {
        $this->channel->push($connection);
    }
}
