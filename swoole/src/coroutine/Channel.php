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
     * @var float
     */
    private $timeout;

    /**
     * Channel constructor.
     */
    public function __construct(int $size, float $timeout = 0)
    {
        $this->channel = new SwooleChannel($size);
        $this->timeout = $timeout;
    }

    /**
     * {@inheritdoc}
     */
    public function push($data, float $timeout = null): bool
    {
        return $this->channel->push($data, $timeout ?? $this->timeout);
    }

    /**
     * {@inheritdoc}
     */
    public function pop(float $timeout = null)
    {
        return $this->channel->pop($timeout ?? $this->timeout);
    }

    public function size(): int
    {
        return $this->channel->length();
    }
}
