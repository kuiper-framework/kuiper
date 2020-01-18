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
     * Channel constructor.
     */
    public function __construct(int $size)
    {
        $this->channel = new SwooleChannel($size);
    }

    /**
     * {@inheritdoc}
     */
    public function push($data, float $timeout = null): bool
    {
        return $this->channel->push($data, $timeout);
    }

    /**
     * {@inheritdoc}
     */
    public function pop(float $timeout = null)
    {
        return $this->channel->pop($timeout);
    }

    public function size(): int
    {
        return $this->channel->length();
    }
}
