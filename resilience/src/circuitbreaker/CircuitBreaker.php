<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

interface CircuitBreaker
{
    public function decorate(callable $call): callable;

    /**
     * @param mixed ...$args
     *
     * @return mixed
     */
    public function call(callable $call, ...$args);

    public function getMetrics(): CircuitBreakerMetrics;
}
