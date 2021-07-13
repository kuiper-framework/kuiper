<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

class SimpleCounterFactory implements CounterFactory
{
    public function create(): Counter
    {
        return new SimpleCounter();
    }
}
