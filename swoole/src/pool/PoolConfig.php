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

namespace kuiper\swoole\pool;

use kuiper\helper\Text;

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
     * Specifies the interval in seconds before a physical connection is discarded.
     *
     * @var float
     */
    private $agedTimeout = -1;

    /**
     * PoolConfig constructor.
     * options:
     *  - max_connections
     *  - wait_timeout
     *  - aged_timeout.
     */
    public function __construct(array $options = [])
    {
        foreach ($options as $key => $value) {
            $key = lcfirst(Text::camelCase($key, '_-'));
            if ('maxConnections' === $key) {
                $this->maxConnections = (int) $value;
            } elseif ('waitTimeout' === $key) {
                $this->waitTimeout = (float) $value;
            } elseif ('agedTimeout' === $key) {
                $this->agedTimeout = (float) $value;
            }
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

    /**
     * @return float
     */
    public function getAgedTimeout(): float
    {
        return $this->agedTimeout;
    }
}
