<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

class TracedSimpleCounterFactory extends AbstractCounterFactory
{
    /**
     * @var string[]
     */
    private $actions;

    protected function createInternal(string $name): Counter
    {
        return new TracedSimpleCounter($this, $name);
    }

    public function trace(string $log): void
    {
        $this->actions[] = $log;
    }

    /**
     * @return string[]
     */
    public function getActions(): array
    {
        return $this->actions;
    }
}
