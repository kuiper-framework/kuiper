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

namespace kuiper\resilience\circuitbreaker\event;

use kuiper\resilience\circuitbreaker\CircuitBreaker;

class CircuitBreakerOnFailureRateExceeded
{
    public function __construct(
        private readonly CircuitBreaker $circuitBreaker,
        private readonly float $failureRate)
    {
    }

    public function getCircuitBreaker(): CircuitBreaker
    {
        return $this->circuitBreaker;
    }

    public function getFailureRate(): float
    {
        return $this->failureRate;
    }
}
