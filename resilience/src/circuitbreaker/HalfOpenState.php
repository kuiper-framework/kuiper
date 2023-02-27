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
use kuiper\resilience\core\Counter;

class HalfOpenState implements CircuitBreakerState
{
    public function __construct(
        private readonly CircuitBreaker $circuitBreaker,
        private readonly int $attempts,
        private readonly CircuitBreakerMetrics $metrics,
        private readonly Counter $permittedNumberOfCalls)
    {
    }

    public function tryAcquirePermission(): bool
    {
        if (($this->permittedNumberOfCalls->get() > 0) && $this->permittedNumberOfCalls->decrement() >= 0) {
            return true;
        }
        $this->circuitBreaker->transitionToOpenState();
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
        $this->permittedNumberOfCalls->increment();
    }

    public function onError(int $duration, Exception $exception): void
    {
        $this->checkIfThresholdsExceeded($this->metrics->onError($duration));
    }

    public function onSuccess(int $duration): void
    {
        $this->checkIfThresholdsExceeded($this->metrics->onSuccess($duration));
    }

    public function attempts(): int
    {
        return $this->attempts;
    }

    public function getState(): State
    {
        return State::HALF_OPEN;
    }

    private function checkIfThresholdsExceeded(Result $result): void
    {
        if (Result::hasExceededThresholds($result)) {
            $this->circuitBreaker->transitionToOpenState();
        }
        if (Result::BELOW_THRESHOLDS === $result) {
            $this->circuitBreaker->transitionToCloseState();
        }
    }
}
