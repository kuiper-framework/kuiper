<?php

declare(strict_types=1);

namespace kuiper\swoole\pool;

class PoolConfig
{
    /**
     * Min connections of pool.
     * This means the pool will create $minConnections connections when
     * pool initialization.
     *
     * @var int
     */
    private $minConnections = 1;

    /**
     * Max connections of pool.
     *
     * @var int
     */
    private $maxConnections = 10;

    /**
     * The timeout of connect the connection.
     * Default value is 10 seconds.
     *
     * @var float
     */
    private $connectTimeout = 10.0;

    /**
     * The timeout of pop a connection.
     * Default value is 3 seconds.
     *
     * @var float
     */
    private $waitTimeout = 3.0;

    /**
     * Heartbeat of connection.
     * If the value is 10, then means 10 seconds.
     * If the value is -1, then means does not need the heartbeat.
     * Default value is -1.
     *
     * @var float
     */
    private $heartbeat = -1;

    /**
     * The max idle time for connection.
     *
     * @var float
     */
    private $maxIdleTime = -1;

    /**
     * PoolConfig constructor.
     * options:
     *  - max-connections
     *  - wait-timeout.
     */
    public function __construct(array $options = [])
    {
        if (isset($options['max-connections'])) {
            $this->maxConnections = (int) $options['max-connections'];
        }
        if (isset($options['wait-timeout'])) {
            $this->waitTimeout = (float) $options['wait-timeout'];
        }
    }

    public function getMaxConnections(): int
    {
        return $this->maxConnections;
    }

    public function getWaitTimeout(): float
    {
        return $this->waitTimeout;
    }
}
