<?php

declare(strict_types=1);

namespace kuiper\metrics\metric;

interface MetricInterface
{
    public function withMetricId(MetricId $metricId): static;

    public function getMetricId(): MetricId;

    public function clear(): void;
}
