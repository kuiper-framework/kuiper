<?php

declare(strict_types=1);

namespace kuiper\metrics\metric;

class Gauge extends Metric implements GaugeInterface
{
    private ?float $value = null;

    public function value(): ?float
    {
        return $this->value;
    }

    public function set(float $value): void
    {
        $this->value = $value;
    }

    public function clear(): void
    {
        $this->value = null;
    }
}
