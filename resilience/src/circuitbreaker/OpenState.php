<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

use kuiper\resilience\circuitbreaker\exception\CallNotPermittedException;

class OpenState implements CircuitBreakerState
{
    /**
     * @var int
     */
    private $attempts;
    /**
     * @var CircuitBreakerImpl
     */
    private $circuitBreaker;
    /**
     * @var CircuitBreakerMetricsImpl
     */
    private $metrics;
    /**
     * @var int
     */
    private $retryAfterWaitDuration;

    public function __construct(CircuitBreakerImpl $circuitBreaker, int $attempts, int $stateChangeTime)
    {
        $this->circuitBreaker = $circuitBreaker;
        /* @phpstan-ignore-next-line */
        $this->metrics = $circuitBreaker->getMetrics();
        $this->attempts = $attempts;
        $this->retryAfterWaitDuration = $stateChangeTime + $circuitBreaker->getConfig()->getWaitIntervalInOpenState($this->attempts);
    }

    public function tryAcquirePermission(): bool
    {
        if ($this->circuitBreaker->getCurrentTimestamp() > $this->retryAfterWaitDuration) {
            $this->circuitBreaker->transitionToHalfOpen();

            return $this->circuitBreaker->tryAcquirePermission();
        }
        $this->metrics->onCallNotPermitted();

        return false;
    }

    public function acquirePermission(): void
    {
        if (!$this->tryAcquirePermission()) {
            throw new CallNotPermittedException();
        }
    }

    public function releasePermission(): void
    {
        // noOp
    }

    public function onError(int $duration, \Exception $exception): void
    {
        $this->metrics->onError($duration);
    }

    public function onSuccess(int $duration): void
    {
        $this->metrics->onSuccess($duration);
    }

    public function attempts(): int
    {
        return $this->attempts;
    }

    public function getState(): State
    {
        return State::OPEN();
    }
}
