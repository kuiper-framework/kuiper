<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

class SimpleCounterFactory extends AbstractCounterFactory
{
    protected function createInternal(string $name): Counter
    {
        return new SimpleCounter();
    }
}
