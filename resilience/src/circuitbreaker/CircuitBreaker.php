<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

interface CircuitBreaker
{
    public function decorate(callable $call): callable;

    /**
     * @return mixed
     */
    public function call(callable $call, array $args);

    public function getMetrics(): CircuitBreakerMetrics;
}
