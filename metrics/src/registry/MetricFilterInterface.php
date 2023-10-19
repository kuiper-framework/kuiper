<?php

declare(strict_types=1);

namespace kuiper\metrics\registry;

use kuiper\metrics\metric\MetricId;

interface MetricFilterInterface
{
    public function accept(MetricId $metricId): bool;

    public function map(MetricId $metricId): MetricId;
}
