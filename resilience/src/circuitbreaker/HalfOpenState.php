<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

use kuiper\resilience\circuitbreaker\exception\CallNotPermittedException;
use kuiper\resilience\core\Counter;

class HalfOpenState implements CircuitBreakerState
{
    /**
     * @var CircuitBreakerImpl
     */
    private $circuitBreaker;
    /**
     * @var int
     */
    private $attempts;
    /**
     * @var CircuitBreakerMetricsImpl
     */
    private $metrics;
    /**
     * @var Counter
     */
    private $permittedNumberOfCalls;

    public function __construct(CircuitBreaker $circuitBreaker, int $attempts, CircuitBreakerMetrics $metrics, Counter $permittedNumberOfCalls)
    {
        /* @phpstan-ignore-next-line */
        $this->circuitBreaker = $circuitBreaker;
        $this->attempts = $attempts;
        $this->permittedNumberOfCalls = $permittedNumberOfCalls;
        /* @phpstan-ignore-next-line */
        $this->metrics = $metrics;
    }

    public function tryAcquirePermission(): bool
    {
        if ($this->permittedNumberOfCalls->get() > 0) {
            if ($this->permittedNumberOfCalls->decrement() < 0) {
                $this->permittedNumberOfCalls->set(0);

                return false;
            }

            return true;
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
        $this->permittedNumberOfCalls->increment();
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
        return $this->attempts;
    }

    public function getState(): State
    {
        return State::HALF_OPEN();
    }

    private function checkIfThresholdsExceeded(Result $result): void
    {
        if (Result::hasExceededThresholds($result)) {
            $this->circuitBreaker->transitionToOpenState();
        }
        if (Result::BELOW_THRESHOLDS === $result->value) {
            $this->circuitBreaker->transitionToCloseState();
        }
    }
}
