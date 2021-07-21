<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker\event;

use kuiper\resilience\circuitbreaker\CircuitBreaker;

class CircuitBreakerOnSlowCallRateExceeded
{
    /**
     * @var CircuitBreaker
     */
    private $circuitBreaker;

    /**
     * @var float
     */
    private $slowCallRate;

    /**
     * CircuitBreakerOnSlowCallRateExceeded constructor.
     */
    public function __construct(CircuitBreaker $circuitBreaker, float $slowCallRate)
    {
        $this->circuitBreaker = $circuitBreaker;
        $this->slowCallRate = $slowCallRate;
    }

    public function getCircuitBreaker(): CircuitBreaker
    {
        return $this->circuitBreaker;
    }

    public function getSlowCallRate(): float
    {
        return $this->slowCallRate;
    }
}
