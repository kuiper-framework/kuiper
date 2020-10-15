<?php

declare(strict_types=1);

namespace kuiper\swoole\coroutine;

use Swoole\Coroutine\Channel as SwooleChannel;

class Channel implements ChannelInterface
{
    /**
     * @var SwooleChannel
     */
    private $channel;

    /**
     * @var \SplQueue
     */
    private $queue;

    /**
     * @var float
     */
    private $timeout;

    /**
     * Channel constructor.
     */
    public function __construct(int $size, float $timeout = 0)
    {
        $this->channel = new SwooleChannel($size);
        $this->queue = new \SplQueue();
        $this->timeout = $timeout;
    }

    /**
     * {@inheritdoc}
     */
    public function push($data, float $timeout = null): bool
    {
        if (Coroutine::isEnabled()) {
            return $this->channel->push($data, $timeout ?? $this->timeout);
        } else {
            $this->queue->push($data);

            return true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function pop(float $timeout = null)
    {
        if (Coroutine::isEnabled()) {
            return $this->channel->pop($timeout ?? $this->timeout);
        } else {
            if (0 === $this->queue->count()) {
                return false;
            }

            return $this->queue->shift();
        }
    }

    public function size(): int
    {
        if (Coroutine::isEnabled()) {
            return $this->channel->length();
        } else {
            return $this->queue->count();
        }
    }
}
