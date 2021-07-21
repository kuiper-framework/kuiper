<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

abstract class AbstractCounterFactory implements CounterFactory
{
    /**
     * @var Counter[]
     */
    private $counters;

    /**
     * {@inheritDoc}
     */
    public function create(string $name): Counter
    {
        if (!isset($this->counters[$name])) {
            $this->counters[$name] = $this->createInternal($name);
        }

        return $this->counters[$name];
    }

    abstract protected function createInternal(string $name): Counter;
}
