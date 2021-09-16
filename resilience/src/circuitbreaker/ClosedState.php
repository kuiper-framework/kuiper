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

use kuiper\resilience\circuitbreaker\event\CircuitBreakerOnFailureRateExceeded;

class ClosedState implements CircuitBreakerState
{
    /**
     * @var CircuitBreakerImpl
     */
    private $circuitBreaker;

    /**
     * @var CircuitBreakerMetricsImpl
     */
    private $metrics;

    /**
     * ClosedState constructor.
     *
     * @param CircuitBreakerImpl $circuitBreaker
     */
    public function __construct(CircuitBreaker $circuitBreaker)
    {
        $this->circuitBreaker = $circuitBreaker;
        /* @phpstan-ignore-next-line */
        $this->metrics = $circuitBreaker->getMetrics();
    }

    public function tryAcquirePermission(): bool
    {
        return true;
    }

    public function acquirePermission(): void
    {
        // noOp
    }

    public function releasePermission(): void
    {
        // noOp
    }

    public function onError(int $duration, \Exception $exception): void
    {
        $this->checkIfThresholdsExceeded($this->metrics->onError($duration));
    }

    public function onSuccess(int $duration): void
    {
        $this->checkIfThresholdsExceeded($this->metrics->onSuccess($duration));
    }

    public function attempts(): int
    {
        return 0;
    }

    public function getState(): State
    {
        return State::CLOSED();
    }

    private function checkIfThresholdsExceeded(Result $result): void
    {
        if (Result::hasExceededThresholds($result)) {
            if (Result::hasFailureRateExceededThreshold($result)) {
                $this->circuitBreaker->getEventDispatcher()->dispatch(new CircuitBreakerOnFailureRateExceeded(
                        $this->circuitBreaker, $this->metrics->getFailureRate()));
            }
            $this->circuitBreaker->transitionToOpenState();
        }
    }
}
