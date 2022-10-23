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
     * @var Clock
     */
    private readonly Clock $clock;

    /**
     * @var PoolInterface[]
     */
    private array $circuitBreakerPoolList = [];

    /**
     * @var CircuitBreaker[]
     */
    private array $circuitBreakerList = [];

    private ?string $proxyClass = null;

    public function __construct(
        private readonly PoolFactoryInterface $poolFactory,
        private readonly StateStore $stateStore,
        private readonly CounterFactory $counterFactory,
        private readonly MetricsFactory $metricFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly array $options = [])
    {
        $this->clock = new SimpleClock();
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
        $circuitBreaker = new CircuitBreakerImpl(
            $name,
            $this->createConfig($name),
            $this->clock,
            $this->stateStore,
            $this->counterFactory,
            $this->metricFactory
        );
        $circuitBreaker->setEventDispatcher($this->eventDispatcher);

        return $circuitBreaker;
    }
}
