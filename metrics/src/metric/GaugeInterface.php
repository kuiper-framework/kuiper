<?php

declare(strict_types=1);

namespace kuiper\metrics\metric;

interface GaugeInterface extends MetricInterface
{
    public function set(float $value): void;

    public function value(): ?float;
}
