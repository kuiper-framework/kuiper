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

use kuiper\logger\Logger;
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
     * @var callable
     */
    private $connectionFactory;

    private ?ConnectionInterface $connection = null;

    private static int $CONNECTION_ID = 1;

    /**
     * SingleConnectionPool constructor.
     */
    public function __construct(
        private readonly string $poolName,
        callable $connectionFactory,
        private readonly PoolConfig $poolConfig)
    {
        $this->connectionFactory = $connectionFactory;
        $this->setLogger(Logger::nullLogger());
    }

    /**
     * {@inheritdoc}
     */
    public function take(): mixed
    {
        if (isset($this->connection)
            && $this->poolConfig->getAgedTimeout() > 0
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

        return $this->connection->getResource();
    }

    public function release(mixed $connection): void
    {
    }

    public function getName(): string
    {
        return $this->poolName;
    }

    public function getConnections(): array
    {
        return [$this->connection->getResource()];
    }

    public function close(): void
    {
        if (isset($this->connection)) {
            $this->connection->close();
        }
        unset($this->connectionFactory, $this->connection);
    }
}
