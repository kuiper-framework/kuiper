<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

class ForcedOpenState implements CircuitBreakerState
{
    /**
     * @var int
     */
    private $attempts;

    public function tryAcquirePermission(): bool
    {
        circuitBreakerMetrics.onCallNotPermitted();

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
        // noOp
    }

    public function onSuccess(int $duration): void
    {
        // noOp
    }

    public function attempts(): int
    {
        return $this->attempts;
    }

    public function getState(): State
    {
        return State::FORCED_OPEN();
    }
}
