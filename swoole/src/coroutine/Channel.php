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

namespace kuiper\swoole\coroutine;

use Swoole\Coroutine\Channel as SwooleChannel;

class Channel implements ChannelInterface
{
    private readonly SwooleChannel $channel;

    private readonly \SplQueue $queue;

    /**
     * Channel constructor.
     */
    public function __construct(
        int $size,
        private readonly float $timeout = 0)
    {
        $this->channel = new SwooleChannel($size);
        $this->queue = new \SplQueue();
    }

    /**
     * {@inheritdoc}
     */
    public function push(mixed $data, float $timeout = null): bool
    {
        if (Coroutine::isEnabled()) {
            return $this->channel->push($data, $timeout ?? $this->timeout);
        }

        $this->queue->push($data);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function pop(float $timeout = null): mixed
    {
        if (Coroutine::isEnabled()) {
            return $this->channel->pop($timeout ?? $this->timeout);
        }

        if (0 === $this->queue->count()) {
            return false;
        }

        return $this->queue->shift();
    }

    public function size(): int
    {
        if (Coroutine::isEnabled()) {
            return $this->channel->length();
        }

        return $this->queue->count();
    }
}
