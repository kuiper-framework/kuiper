<?php

declare(strict_types=1);

namespace kuiper\resilience\retry;

use kuiper\resilience\core\Clock;
use kuiper\resilience\core\Counter;
use kuiper\resilience\core\CounterFactory;
use kuiper\resilience\retry\event\RetryOnError;
use kuiper\resilience\retry\event\RetryOnIgnoredError;
use kuiper\resilience\retry\event\RetryOnRetry;
use kuiper\resilience\retry\event\RetryOnSuccess;
use kuiper\resilience\retry\exception\MaxRetriesExceededException;
use Psr\EventDispatcher\EventDispatcherInterface;

class RetryImpl implements Retry
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var RetryConfig
     */
    private $config;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var int
     */
    private $numOfAttempts;

    /**
     * @var Counter
     */
    private $succeededAfterRetryCounter;
    /**
     * @var Counter
     */
    private $succeededWithoutRetryCounter;

    /**
     * @var Counter
     */
    private $failedAfterRetryCounter;

    /**
     * @var Counter
     */
    private $failedWithoutRetryCounter;

    /**
     * @var \Exception|null
     */
    private $lastException;
    /**
     * @var Clock
     */
    private $clock;

    public function __construct(string $name, RetryConfig $config, Clock $clock, CounterFactory $counterFactory, EventDispatcherInterface $eventDispatcher)
    {
        $this->name = $name;
        $this->config = $config;
        $this->numOfAttempts = 0;
        $this->clock = $clock;
        $this->succeededAfterRetryCounter = $counterFactory->create($this->name.'.succeeded_retry');
        $this->succeededWithoutRetryCounter = $counterFactory->create($this->name.'.succeeded');
        $this->failedAfterRetryCounter = $counterFactory->create($this->name.'.failed_retry');
        $this->failedWithoutRetryCounter = $counterFactory->create($this->name.'.failed');
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function decorate(callable $call): callable
    {
        return function () use ($call) {
            do {
                try {
                    $result = $call(...func_get_args());
                    $shouldRetry = $this->onResult($result);
                    if (!$shouldRetry) {
                        $this->onComplete();

                        return $result;
                    }
                } catch (\Exception $e) {
                    $this->onError($e);
                }
            } while (true);
        };
    }

    /**
     * {@inheritDoc}
     */
    public function call(callable $call, ...$args)
    {
        return $this->decorate($call)(...$args);
    }

    public function getMetrics(): RetryMetrics
    {
        return new RetryMetricsImpl(
            $this->succeededAfterRetryCounter->get(),
            $this->succeededWithoutRetryCounter->get(),
            $this->failedAfterRetryCounter->get(),
            $this->failedWithoutRetryCounter->get()
        );
    }

    protected function onComplete(): void
    {
        $currentNumOfAttempts = $this->numOfAttempts;
        if ($currentNumOfAttempts > 0 && $currentNumOfAttempts < $this->config->getMaxAttempts()) {
            // 没有达到重试次数
            $this->succeededAfterRetryCounter->increment();
            $this->eventDispatcher->dispatch(new RetryOnSuccess($this, $currentNumOfAttempts, $this->lastException));
        } elseif ($currentNumOfAttempts >= $this->config->getMaxAttempts()) {
            // 达到重试次数
            $this->failedAfterRetryCounter->increment();
            $e = $this->lastException;
            if ((null === $e) && $this->config->isFailAfterMaxAttempts()) {
                $e = new MaxRetriesExceededException('max retries is reached out for the result predicate check');
            }
            $this->eventDispatcher->dispatch(new RetryOnError($this, $currentNumOfAttempts, $e));

            if ($this->config->isFailAfterMaxAttempts()) {
                throw new MaxRetriesExceededException();
            }
        } else {
            // 首次调用
            $this->succeededWithoutRetryCounter->increment();
        }
    }

    /**
     * @param mixed $result
     */
    protected function onResult($result): bool
    {
        if (null === $this->config->getRetryOnResult()) {
            return false;
        }
        /** @var bool $shouldRetry */
        $shouldRetry = call_user_func($this->config->getRetryOnResult(), $result);
        if ($shouldRetry) {
            $currentNumberOfAttempts = ++$this->numOfAttempts;
            if ($currentNumberOfAttempts >= $this->config->getMaxAttempts()) {
                return false;
            }

            $this->waitIntervalAfterFailure($currentNumberOfAttempts, null, $result);

            return true;
        }

        return false;
    }

    protected function onError(\Exception $exception): void
    {
        if ($this->config->shouldRetryOnException($exception)) {
            $this->lastException = $exception;
            $this->throwOrSleepAfterException();
        } else {
            $this->failedWithoutRetryCounter->increment();
            $this->eventDispatcher->dispatch(new RetryOnIgnoredError($this, $exception));
            throw $exception;
        }
    }

    private function throwOrSleepAfterException(): void
    {
        $currentNumOfAttempts = ++$this->numOfAttempts;
        if ($currentNumOfAttempts >= $this->config->getMaxAttempts()) {
            $this->failedAfterRetryCounter->increment();
            $this->eventDispatcher->dispatch(new RetryOnError($this, $currentNumOfAttempts, $this->lastException));
            throw $this->lastException;
        } else {
            $this->waitIntervalAfterFailure($currentNumOfAttempts, $this->lastException, null);
        }
    }

    /**
     * @param mixed $result
     */
    private function waitIntervalAfterFailure(int $numOfAttempts, ?\Exception $exception, $result): void
    {
        $interval = $this->config->getRetryInterval($this->numOfAttempts, $exception, $result);
        $this->eventDispatcher->dispatch(new RetryOnRetry($this, $numOfAttempts, $interval, $exception, $result));
        $this->clock->sleep($interval);
    }
}
