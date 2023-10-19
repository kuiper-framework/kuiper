<?php

declare(strict_types=1);

namespace kuiper\metrics\metric;

class ComposeTimer extends Metric implements TimerInterface
{
    /**
     * @param MetricId         $metricId
     * @param TimerInterface[] $children
     */
    public function __construct(MetricId $metricId, private readonly array $children)
    {
        parent::__construct($metricId);
    }

    public function record(float $duration): void
    {
        foreach ($this->children as $child) {
            $child->record($duration);
        }
    }

    public function value(): array
    {
        return $this->children[0]->value();
    }

    /**
     * @return void
     */
    public function clear(): void
    {
        foreach ($this->children as $child) {
            $child->clear();
        }
    }
}
