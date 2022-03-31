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

namespace kuiper\resilience\circuitbreaker;

use kuiper\helper\Arrays;
use kuiper\resilience\core\Clock;
use kuiper\resilience\core\CounterFactory;
use kuiper\resilience\core\MetricsFactory;
use kuiper\resilience\core\SimpleClock;
use kuiper\swoole\pool\ConnectionProxyGenerator;
use kuiper\swoole\pool\PoolFactoryInterface;
use kuiper\swoole\pool\PoolInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class CircuitBreakerFactoryImpl implements CircuitBreakerFactory
{
    /**
     * @var PoolFactoryInterface
     */
    private $poolFactory;
    /**
     * @var StateStore
     */
    private $stateStore;

    /**
     * @var CounterFactory
     */
    private $counterFactory;
    /**
     * @var MetricsFactory
     */
    private $metricFactory;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var array
     */
    private $options;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var PoolInterface[]
     */
    private $circuitBreakerPoolList;

    /**
     * @var CircuitBreaker[]
     */
    private $circuitBreakerList;

    /**
     * @var string|null
     */
    private $proxyClass;

    public function __construct(PoolFactoryInterface $poolFactory, StateStore $stateStore, CounterFactory $counterFactory, MetricsFactory $metricFactory, EventDispatcherInterface $eventDispatcher, array $options = null)
    {
        $this->poolFactory = $poolFactory;
        $this->stateStore = $stateStore;
        $this->counterFactory = $counterFactory;
        $this->metricFactory = $metricFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->clock = new SimpleClock();
        $this->options = $options ?? [];
    }

    private function getProxyClass(): string
    {
        if (null === $this->proxyClass) {
            $generator = new ConnectionProxyGenerator();
            $result = $generator->generate(CircuitBreaker::class);
            $result->eval();
            $this->proxyClass = $result->getClassName();
        }

        return $this->proxyClass;
    }

    public function create(string $name): CircuitBreaker
    {
        if (!isset($this->circuitBreakerList[$name])) {
            $this->circuitBreakerPoolList[$name] = $this->poolFactory->create('circuitbreaker'.$name, function () use ($name): CircuitBreaker {
                return $this->newInstance($name);
            });
            $class = $this->getProxyClass();
            $this->circuitBreakerList[$name] = new $class($this->circuitBreakerPoolList[$name]);
        }

        return $this->circuitBreakerList[$name];
    }

    /**
     * @return CircuitBreaker[]
     */
    public function getCircuitBreakerList(): array
    {
        return Arrays::flatten(Arrays::pull($this->circuitBreakerPoolList, 'connections'));
    }

    private function createConfig(string $name): CircuitBreakerConfig
    {
        $options = Arrays::filter(array_merge($this->options['default'] ?? [], $this->options[$name] ?? []));
        if (empty($options)) {
            return CircuitBreakerConfig::ofDefaults();
        }
        $configBuilder = CircuitBreakerConfig::builder();
        Arrays::assign($configBuilder, $options);

        return $configBuilder->build();
    }

    /**
     * @param string $name
     *
     * @return CircuitBreakerImpl
     */
    public function newInstance(string $name): CircuitBreaker
    {
        return new CircuitBreakerImpl(
            $name,
            $this->createConfig($name),
            $this->clock,
            $this->stateStore,
            $this->counterFactory,
            $this->metricFactory,
            $this->eventDispatcher
        );
    }
}
