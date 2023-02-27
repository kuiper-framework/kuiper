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

use SplQueue;

class SplQueueChannel implements ChannelInterface
{
    public function __construct(private readonly SplQueue $queue = new SplQueue())
    {
    }

    /**
     * {@inheritdoc}
     */
    public function push(mixed $data, float $timeout = null): bool
    {
        $this->queue->push($data);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function pop(float $timeout = null): mixed
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
