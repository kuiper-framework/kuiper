<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

class ClosedState implements CircuitBreakerState
{
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
        $this->checkIfThresholdsExceeded($this->circuitBreakerMetrics->onError($duration));
    }

    public function onSuccess(int $duration): void
    {
        $this->checkIfThresholdsExceeded($this->circuitBreakerMetrics->onSuccess($duration));
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
            publishCircuitThresholdsExceededEvent(result, circuitBreakerMetrics);
            transitionToOpenState();
        }
    }
}
