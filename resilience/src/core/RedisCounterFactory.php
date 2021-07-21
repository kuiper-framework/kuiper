<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

class RedisCounterFactory extends AbstractCounterFactory
{
    /**
     * @var \Redis
     */
    private $redis;

    /**
     * RedisCounterFactory constructor.
     *
     * @param \Redis $redis
     */
    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    protected function createInternal(string $name): Counter
    {
        return new RedisCounter($this->redis, $name);
    }
}
