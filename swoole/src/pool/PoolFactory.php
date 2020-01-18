<?php

declare(strict_types=1);

namespace kuiper\swoole\pool;

class PoolFactory implements PoolFactoryInterface
{
    /**
     * @var PoolConfig
     */
    private $config;

    /**
     * PoolFactory constructor.
     */
    public function __construct(PoolConfig $config)
    {
        $this->config = $config;
    }

    public function create(callable $connectionFactory): PoolInterface
    {
        return new SimplePool($connectionFactory, $this->config);
    }
}
