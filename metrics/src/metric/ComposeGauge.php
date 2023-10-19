<?php

declare(strict_types=1);

namespace kuiper\metrics\metric;

class ComposeGauge extends Metric implements GaugeInterface
{
    /**
     * @param MetricId         $metricId
     * @param GaugeInterface[] $children
     */
    public function __construct(MetricId $metricId, private readonly array $children)
    {
        parent::__construct($metricId);
    }

    public function set(float $value = 1.0): void
    {
        foreach ($this->children as $child) {
            $child->set($value);
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
