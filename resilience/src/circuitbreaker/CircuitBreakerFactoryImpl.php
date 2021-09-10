<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

use kuiper\helper\Arrays;
use kuiper\resilience\core\CounterFactory;
use kuiper\resilience\core\MetricsFactory;
use kuiper\resilience\core\SimpleClock;
use Psr\EventDispatcher\EventDispatcherInterface;

class CircuitBreakerFactoryImpl implements CircuitBreakerFactory
{
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
     * @var CircuitBreaker[]
     */
    private $circuitBreakerList;

    public function __construct(StateStore $stateStore, CounterFactory $counterFactory, MetricsFactory $metricFactory, EventDispatcherInterface $eventDispatcher, array $options = null)
    {
        $this->stateStore = $stateStore;
        $this->counterFactory = $counterFactory;
        $this->metricFactory = $metricFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->options = $options ?? [];
    }

    public function create(string $name): CircuitBreaker
    {
        if (!isset($this->circuitBreakerList[$name])) {
            $this->circuitBreakerList[$name] = new CircuitBreakerImpl(
                $name,
                $this->createConfig($name),
                new SimpleClock(),
                $this->stateStore,
                $this->counterFactory,
                $this->metricFactory,
                $this->eventDispatcher
            );
        }
        $circuitBreaker = $this->circuitBreakerList[$name];
        $circuitBreaker->reset();

        return $circuitBreaker;
    }

    /**
     * @return CircuitBreaker[]
     */
    public function getCircuitBreakerList(): array
    {
        return $this->circuitBreakerList;
    }

    private function createConfig(string $name): CircuitBreakerConfig
    {
        if (!isset($this->options[$name])) {
            return CircuitBreakerConfig::ofDefaults();
        }
        $configBuilder = CircuitBreakerConfig::builder();
        Arrays::assign($configBuilder, $this->options[$name]);

        return $configBuilder->build();
    }
}
