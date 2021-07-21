<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

class RedisCounter implements Counter
{
    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @var string
     */
    private $key;

    /**
     * RedisCounter constructor.
     *
     * @param \Redis $redis
     */
    public function __construct($redis, string $key)
    {
        $this->redis = $redis;
        $this->key = $key;
    }

    public function increment(int $value = 1): int
    {
        return $this->redis->incrBy($this->key, $value);
    }

    public function get(): int
    {
        return (int) $this->redis->get($this->key);
    }

    public function set(int $value): void
    {
        $this->redis->set($this->key, $value);
    }

    public function decrement(int $value = 1): int
    {
        return $this->redis->decrBy($this->key, $value);
    }
}
