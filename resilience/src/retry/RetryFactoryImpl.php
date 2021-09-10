<?php

declare(strict_types=1);

namespace kuiper\resilience\retry;

use kuiper\helper\Arrays;
use kuiper\resilience\core\CounterFactory;
use kuiper\resilience\core\SimpleClock;
use Psr\EventDispatcher\EventDispatcherInterface;

class RetryFactoryImpl implements RetryFactory
{
    /**
     * @var CounterFactory
     */
    private $counterFactory;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var array
     */
    private $options;
    /**
     * @var Retry[]
     */
    private $retryList;

    /**
     * RetryFactoryImpl constructor.
     *
     * @param CounterFactory           $counterFactory
     * @param EventDispatcherInterface $eventDispatcher
     * @param array                    $options
     */
    public function __construct(CounterFactory $counterFactory, EventDispatcherInterface $eventDispatcher, array $options = null)
    {
        $this->counterFactory = $counterFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->options = $options ?? [];
    }

    /**
     * @return Retry[]
     */
    public function getRetryList(): array
    {
        return $this->retryList;
    }

    /**
     * {@inheritDoc}
     */
    public function create(string $name): Retry
    {
        $retry = $this->retryList[$name] ?? null;
        if (!isset($retry)) {
            $this->retryList[$name] = $retry = new RetryImpl($name, $this->createConfig($name), new SimpleClock(), $this->counterFactory, $this->eventDispatcher);
        }
        $retry->reset();

        return $retry;
    }

    private function createConfig(string $name): RetryConfig
    {
        if (!isset($this->options[$name])) {
            [$name] = explode('::', $name);
        }
        if (!isset($this->options[$name])) {
            return RetryConfig::ofDefaults();
        }
        $configBuilder = RetryConfig::builder();
        Arrays::assign($configBuilder, $this->options[$name]);

        return $configBuilder->build();
    }
}
