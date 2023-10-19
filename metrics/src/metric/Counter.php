<?php

declare(strict_types=1);

namespace kuiper\metrics\metric;

class Counter extends Metric implements CounterInterface
{
    private ?float $value = null;

    public function increment(float $amount = 1.0): void
    {
        $this->value += $amount;
    }

    public function value(): ?float
    {
        return $this->value;
    }

    public function clear(): void
    {
        $this->value = null;
    }
}
