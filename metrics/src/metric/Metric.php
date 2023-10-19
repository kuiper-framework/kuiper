<?php

declare(strict_types=1);

namespace kuiper\metrics\metric;

abstract class Metric implements MetricInterface
{
    protected MetricId $metricId;

    private static array $instances = [];

    public static function create(MetricId $metricId): MetricInterface
    {
        $metricKey = (string) $metricId;
        if (!isset(self::$instances[$metricKey])) {
            self::$instances[$metricKey] = new static($metricId);
        }

        return self::$instances[$metricKey];
    }

    public function __construct(MetricId $metricId)
    {
        $this->metricId = $metricId;
    }

    public function withMetricId(MetricId $metricId): static
    {
        $copy = clone $this;
        $copy->metricId = $metricId;

        return $copy;
    }

    public function getMetricId(): MetricId
    {
        return $this->metricId;
    }

    public function clear(): void
    {
    }
}
