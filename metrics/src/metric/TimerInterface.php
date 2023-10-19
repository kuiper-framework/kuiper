<?php

declare(strict_types=1);

namespace kuiper\metrics\metric;

interface TimerInterface extends MetricInterface
{
    public function record(float $duration): void;

    public function value(): array;
}
