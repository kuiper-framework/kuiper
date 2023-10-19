<?php

declare(strict_types=1);

namespace kuiper\metrics\metric;

interface CounterInterface extends MetricInterface
{
    public function increment(float $amount = 1.0): void;

    public function value(): ?float;
}
