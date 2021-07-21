<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker\event;

use kuiper\resilience\circuitbreaker\CircuitBreaker;
use kuiper\resilience\circuitbreaker\State;

class CircuitBreakerOnStateTransition
{
    /**
     * @var CircuitBreaker
     */
    private $circuitBreaker;

    /**
     * @var State
     */
    private $fromState;

    /**
     * @var State
     */
    private $toState;

    /**
     * CircuitBreakerOnStateTransition constructor.
     */
    public function __construct(CircuitBreaker $circuitBreaker, State $fromState, State $toState)
    {
        $this->circuitBreaker = $circuitBreaker;
        $this->fromState = $fromState;
        $this->toState = $toState;
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
