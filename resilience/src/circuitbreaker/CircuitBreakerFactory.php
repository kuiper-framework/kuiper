<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

interface CircuitBreakerFactory
{
    public function create(string $name): CircuitBreaker;
}
