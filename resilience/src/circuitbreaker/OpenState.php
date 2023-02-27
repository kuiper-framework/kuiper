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

use Exception;
use kuiper\resilience\circuitbreaker\exception\CallNotPermittedException;

class OpenState implements CircuitBreakerState
{
    private readonly CircuitBreakerMetrics $metrics;

    private readonly int $retryAfterWaitDuration;

    public function __construct(
        private readonly CircuitBreakerImpl $circuitBreaker,
        private readonly int $attempts,
        int $stateChangeTime)
    {
        $this->metrics = $circuitBreaker->getMetrics();
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

    public function onError(int $duration, Exception $exception): void
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
        return State::OPEN;
    }
}
