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

class DisabledState implements CircuitBreakerState
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
        // noOp
    }

    public function onSuccess(int $duration): void
    {
        // noOp
    }

    public function attempts(): int
    {
        return 0;
    }

    public function getState(): State
    {
        return State::DISABLED;
    }
}
