<?php

declare(strict_types=1);

namespace kuiper\swoole\coroutine;

class SplQueueChannel implements ChannelInterface
{
    /**
     * @var \SplQueue
     */
    private $queue;

    /**
     * SplQueueChannel constructor.
     */
    public function __construct()
    {
        $this->queue = new \SplQueue();
    }

    /**
     * {@inheritdoc}
     */
    public function push($data, float $timeout = null): bool
    {
        $this->queue->push($data);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function pop(float $timeout = null)
    {
        if (0 === $this->queue->count()) {
            return false;
        }

        return $this->queue->shift();
    }

    public function size(): int
    {
        return $this->queue->count();
    }
}
