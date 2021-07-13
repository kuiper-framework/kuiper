<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

use kuiper\resilience\circuitbreaker\exception\CallNotPermittedException;

class OpenState implements CircuitBreakerState
{
    public function tryAcquirePermission(): bool
    {
        if (after($this->config->getRetryAfterWaitDuration())) {
            $this->transitionTo(State::HALF_OPEN());
            $callPermitted = $this->tryAcquirePermission();
            if (!$callPermitted) {
                publishCallNotPermittedEvent();
                circuitBreakerMetrics.onCallNotPermitted();
            }

            return $callPermitted;
        }
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
        $this->circuitBreakerMetrics->onError($duration);
    }

    public function onSuccess(int $duration): void
    {
        $this->circuitBreakerMetrics->onSuccess($duration);
    }

    public function attempts(): int
    {
        // TODO: Implement attempts() method.
    }

    public function getState(): State
    {
        return State::OPEN();
    }
}
