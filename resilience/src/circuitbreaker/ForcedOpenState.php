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

use kuiper\resilience\circuitbreaker\exception\CallNotPermittedException;

class ForcedOpenState implements CircuitBreakerState
{
    /**
     * @var CircuitBreakerMetricsImpl
     */
    private $metrics;
    /**
     * @var int
     */
    private $attempts;

    /**
     * ForcedOpenState constructor.
     *
     * @param CircuitBreakerImpl $circuitBreaker
     */
    public function __construct(CircuitBreaker $circuitBreaker, int $attempts)
    {
        /* @phpstan-ignore-next-line */
        $this->metrics = $circuitBreaker->getMetrics();
        $this->attempts = $attempts;
    }

    public function tryAcquirePermission(): bool
    {
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
