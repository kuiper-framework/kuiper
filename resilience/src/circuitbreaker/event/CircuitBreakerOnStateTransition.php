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
use kuiper\resilience\circuitbreaker\State;

class CircuitBreakerOnStateTransition
{
    public function __construct(
        private readonly CircuitBreaker $circuitBreaker,
        private readonly State $fromState,
        private readonly State $toState)
    {
    }

    public function getCircuitBreaker(): CircuitBreaker
    {
        return $this->circuitBreaker;
    }

    public function getFromState(): State
    {
        return $this->fromState;
    }

    public function getToState(): State
    {
        return $this->toState;
    }
}
