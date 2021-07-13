<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

interface CircuitBreakerState
{
    public function tryAcquirePermission(): bool;

    public function acquirePermission(): void;

    public function releasePermission(): void;

    public function onError(int $duration, \Exception $exception): void;

    public function onSuccess(int $duration): void;

    public function attempts(): int;

    public function getState(): State;
}
