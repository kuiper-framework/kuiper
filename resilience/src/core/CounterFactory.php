<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

interface CounterFactory
{
    /**
     * Creates a counter.
     */
    public function create(string $name): Counter;
}
