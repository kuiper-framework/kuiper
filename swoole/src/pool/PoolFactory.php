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

use kuiper\event\EventDispatcherAwareInterface;
use kuiper\event\EventDispatcherAwareTrait;
use kuiper\event\NullEventDispatcher;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class PoolFactory implements PoolFactoryInterface, LoggerAwareInterface, EventDispatcherAwareInterface
{
    use LoggerAwareTrait;
    use EventDispatcherAwareTrait;

    /**
     * @var PoolConfig[]
     */
    private array $poolConfigMap;

    /**
     * PoolFactory constructor.
     *
     * @param PoolConfig[] $poolConfigMap
     */
    public function __construct(
        private readonly bool $coroutineEnabled = true,
        array $poolConfigMap = [])
    {
        foreach ($poolConfigMap as $poolName => $config) {
            $this->setPoolConfig($poolName, $config);
        }
        $this->setEventDispatcher(new NullEventDispatcher());
        $this->setLogger(\kuiper\logger\Logger::nullLogger());
    }

    public function setPoolConfig(string $poolName, array|PoolConfig $poolConfig): void
    {
        if (is_array($poolConfig)) {
            $poolConfig = new PoolConfig($poolConfig);
        }
        $this->poolConfigMap[$poolName] = $poolConfig;
    }

    public function create(string $poolName, callable $connectionFactory): PoolInterface
    {
        $poolConfig = $this->poolConfigMap[$poolName] ?? new PoolConfig();
        if ($this->coroutineEnabled) {
            $pool = new SimplePool($poolName, $connectionFactory, $poolConfig, $this->eventDispatcher);
        } else {
            $pool = new SingleConnectionPool($poolName, $connectionFactory, $poolConfig);
        }
        $pool->setLogger($this->logger);

        return $pool;
    }
}
