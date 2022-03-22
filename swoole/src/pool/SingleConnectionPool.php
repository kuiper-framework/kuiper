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

use kuiper\swoole\exception\PoolClosedException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class SingleConnectionPool implements PoolInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var string
     */
    private $poolName;
    /**
     * @var callable
     */
    private $connectionFactory;
    /**
     * @var ConnectionInterface|null
     */
    private $connection;
    /**
     * @var PoolConfig
     */
    private $poolConfig;
    /**
     * @var int
     */
    private static $CONNECTION_ID = 1;

    /**
     * SingleConnectionPool constructor.
     */
    public function __construct(string $poolName, callable $connectionFactory, PoolConfig $poolConfig, LoggerInterface $logger = null)
    {
        $this->poolName = $poolName;
        $this->connectionFactory = $connectionFactory;
        $this->poolConfig = $poolConfig;
        $this->setLogger($logger ?? new NullLogger());
    }

    /**
     * {@inheritdoc}
     */
    public function take(): ConnectionInterface
    {
        if (!isset($this->connectionFactory)) {
            throw new PoolClosedException();
        }
        if (isset($this->connection)
            && $this->connection->getCreatedAt() + $this->poolConfig->getAgedTimeout() < time()) {
            $this->connection->close();
            unset($this->connection);
        }
        if (!isset($this->connection)) {
            $this->logger->info(static::TAG."create $this->poolName connection");
            $conn = null;
            $id = self::$CONNECTION_ID++;
            $ret = call_user_func_array($this->connectionFactory, [$id, &$conn]);
            if (null === $conn) {
                $conn = $ret;
            }
            $this->connection = new Connection($id, $conn);
        }

        return $this->connection;
    }

    public function release(): void
    {
    }

    public function getName(): string
    {
        return $this->poolName;
    }

    public function getConnections(): array
    {
        return [$this->connection];
    }

    public function close(): void
    {
        if (isset($this->connection)) {
            $this->connection->close();
        }
        unset($this->connectionFactory, $this->connection);
    }
}
