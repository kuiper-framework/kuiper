<?php

declare(strict_types=1);

namespace kuiper\metrics\registry;

use kuiper\metrics\metric\ComposeCounter;
use kuiper\metrics\metric\ComposeGauge;
use kuiper\metrics\metric\ComposeTimer;
use kuiper\metrics\metric\CounterInterface;
use kuiper\metrics\metric\GaugeInterface;
use kuiper\metrics\metric\MetricId;
use kuiper\metrics\metric\TimerInterface;

class ComposeMetricRegistry extends MetricRegistry
{
    /**
     * @param MetricRegistryInterface[] $registryList
     */
    public function __construct(private readonly array $registryList)
    {
        parent::__construct();
    }

    protected function newCounter(MetricId $metricId): CounterInterface
    {
        $children = [];
        foreach ($this->registryList as $registry) {
            $children[] = $registry->counter($metricId->getName(), $metricId->getTags());
        }

        return new ComposeCounter($metricId, $children);
    }

    protected function newGauge(MetricId $metricId): GaugeInterface
    {
        $children = [];
        foreach ($this->registryList as $registry) {
            $children[] = $registry->gauge($metricId->getName(), $metricId->getTags());
        }

        return new ComposeGauge($metricId, $children);
    }

    protected function newTimer(MetricId $metricId): TimerInterface
    {
        $children = [];
        foreach ($this->registryList as $registry) {
            $children[] = $registry->timer($metricId->getName(), $metricId->getTags());
        }

        return new ComposeTimer($metricId, $children);
    }
}
