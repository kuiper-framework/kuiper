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
