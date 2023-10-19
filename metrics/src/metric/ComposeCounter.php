<?php

declare(strict_types=1);

namespace kuiper\metrics\metric;

class ComposeCounter extends Metric implements CounterInterface
{
    /**
     * @param MetricId           $metricId
     * @param CounterInterface[] $children
     */
    public function __construct(MetricId $metricId, private readonly array $children)
    {
        parent::__construct($metricId);
    }

    public function increment(float $amount = 1.0): void
    {
        foreach ($this->children as $child) {
            $child->increment($amount);
        }
    }

    public function value(): ?float
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
