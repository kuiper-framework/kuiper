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

use kuiper\event\NullEventDispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PoolFactory implements PoolFactoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var PoolConfig[]
     */
    private $poolConfigMap;

    /**
     * @var bool
     */
    private $coroutineEnabled;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * PoolFactory constructor.
     *
     * @param PoolConfig[] $poolConfigMap
     */
    public function __construct(
        bool $coroutineEnabled = true,
        array $poolConfigMap = [],
        ?LoggerInterface $logger = null,
        ?EventDispatcherInterface $eventDispatcher = null)
    {
        $this->coroutineEnabled = $coroutineEnabled;
        $this->eventDispatcher = $eventDispatcher;
        foreach ($poolConfigMap as $poolName => $config) {
            $this->setPoolConfig($poolName, $config);
        }
        $this->eventDispatcher = $eventDispatcher ?? new NullEventDispatcher();
        $this->setLogger($logger ?? new NullLogger());
    }

    /**
     * @param array|PoolConfig|mixed $poolConfig
     */
    public function setPoolConfig(string $poolName, $poolConfig): void
    {
        if (is_array($poolConfig)) {
            $poolConfig = new PoolConfig($poolConfig);
        }
        if (!$poolConfig instanceof PoolConfig) {
            throw new \InvalidArgumentException('invalid pool config '.gettype($poolConfig));
        }
        $this->poolConfigMap[$poolName] = $poolConfig;
    }

    public function create(string $poolName, callable $connectionFactory): PoolInterface
    {
        $poolConfig = $this->poolConfigMap[$poolName] ?? new PoolConfig();
        if ($this->coroutineEnabled) {
            $pool = new SimplePool($poolName, $connectionFactory, $poolConfig, $this->eventDispatcher, $this->logger);
        } else {
            $pool = new SingleConnectionPool($poolName, $connectionFactory, $poolConfig, $this->logger);
        }

        return $pool;
    }
}
