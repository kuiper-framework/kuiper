<?php

declare(strict_types=1);

namespace kuiper\metrics\metric;

class Timer extends Metric implements TimerInterface
{
    private array $records = [];

    public function record(float $duration): void
    {
        $this->records[] = $duration;
    }

    public function value(): array
    {
        return $this->records;
    }

    public function clear(): void
    {
        $this->records = [];
    }
}
