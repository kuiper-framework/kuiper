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

namespace kuiper\resilience\retry;

use Exception;
use kuiper\event\EventDispatcherAwareInterface;
use kuiper\event\EventDispatcherAwareTrait;
use kuiper\resilience\core\Clock;
use kuiper\resilience\core\Counter;
use kuiper\resilience\core\CounterFactory;
use kuiper\resilience\retry\event\RetryOnError;
use kuiper\resilience\retry\event\RetryOnIgnoredError;
use kuiper\resilience\retry\event\RetryOnRetry;
use kuiper\resilience\retry\event\RetryOnSuccess;
use kuiper\resilience\retry\exception\MaxRetriesExceededException;
use Throwable;

class RetryImpl implements Retry, EventDispatcherAwareInterface
{
    use EventDispatcherAwareTrait;

    private int $numOfAttempts = 0;

    private readonly Counter $succeededAfterRetryCounter;

    private readonly Counter $succeededWithoutRetryCounter;

    private readonly Counter $failedAfterRetryCounter;

    private readonly Counter $failedWithoutRetryCounter;

    private ?Throwable $lastException = null;

    public function __construct(
        private readonly string $name,
        private readonly RetryConfig $config,
        private readonly Clock $clock,
        CounterFactory $counterFactory)
    {
        $this->succeededAfterRetryCounter = $counterFactory->create($this->name.'.succeeded_retry');
        $this->succeededWithoutRetryCounter = $counterFactory->create($this->name.'.succeeded');
        $this->failedAfterRetryCounter = $counterFactory->create($this->name.'.failed_retry');
        $this->failedWithoutRetryCounter = $counterFactory->create($this->name.'.failed');
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return RetryConfig
     */
    public function getConfig(): RetryConfig
    {
        return $this->config;
    }

    /**
     * @return int
     */
    public function getNumOfAttempts(): int
    {
        return $this->numOfAttempts;
    }

    public function getLastException(): ?Throwable
    {
        return $this->lastException;
    }

    /**
     * {@inheritDoc}
     */
    public function decorate(callable $call): callable
    {
        return function () use ($call) {
            $this->reset();
            while (true) {
                try {
                    $result = $call(...func_get_args());
                    $shouldRetry = $this->onResult($result);
                    if (!$shouldRetry) {
                        $this->onComplete();

                        return $result;
                    }
                } catch (Exception $e) {
                    $this->onError($e);
                }
            }
        };
    }

    /**
     * {@inheritDoc}
     */
    public function call(callable $call, ...$args): mixed
    {
        return $this->decorate($call)(...$args);
    }

    public function reset(): void
    {
        $this->numOfAttempts = 0;
        $this->lastException = null;
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
                throw $e;
            }
        } else {
            // 首次调用
            $this->succeededWithoutRetryCounter->increment();
        }
    }

    protected function onResult(mixed $result): bool
    {
        if (null === $this->config->getRetryOnResult()) {
            return false;
        }
        /** @var bool $shouldRetry */
        $shouldRetry = call_user_func($this->config->getRetryOnResult(), $this, $result);
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

    protected function onError(Throwable $exception): void
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
        }

        $this->waitIntervalAfterFailure($currentNumOfAttempts, $this->lastException, null);
    }

    private function waitIntervalAfterFailure(int $numOfAttempts, ?Throwable $exception, mixed $result): void
    {
        $interval = $this->config->getRetryInterval($numOfAttempts, $exception, $result);
        $this->eventDispatcher->dispatch(new RetryOnRetry($this, $numOfAttempts, $interval, $exception, $result));
        $this->clock->sleep($interval);
    }
}
