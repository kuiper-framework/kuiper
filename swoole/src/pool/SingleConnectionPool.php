<?php

declare(strict_types=1);

namespace kuiper\swoole\pool;

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
     * @var object|null
     */
    private $connection;
    /**
     * @var int
     */
    private static $CONNECTION_ID = 1;

    /**
     * SingleConnectionPool constructor.
     */
    public function __construct(string $poolName, callable $connectionFactory, LoggerInterface $logger = null)
    {
        $this->poolName = $poolName;
        $this->connectionFactory = $connectionFactory;
        $this->setLogger($logger ?? new NullLogger());
    }

    /**
     * {@inheritdoc}
     */
    public function take()
    {
        if (!isset($this->connection)) {
            $this->logger->info(static::TAG."create $this->poolName connection");
            $this->connection = call_user_func($this->connectionFactory, self::$CONNECTION_ID++);
        }

        return $this->connection;
    }

    public function reset(): void
    {
        $this->connection = null;
    }

    public function getName(): string
    {
        return $this->poolName;
    }

    public function getConnections(): array
    {
        return [$this->connection];
    }
}
