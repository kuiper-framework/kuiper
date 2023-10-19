<?php

declare(strict_types=1);

namespace kuiper\metrics\registry;

use InvalidArgumentException;
use kuiper\helper\Arrays;
use kuiper\metrics\metric\Counter;
use kuiper\metrics\metric\CounterInterface;
use kuiper\metrics\metric\Gauge;
use kuiper\metrics\metric\GaugeInterface;
use kuiper\metrics\metric\MetricId;
use kuiper\metrics\metric\MetricInterface;
use kuiper\metrics\metric\MetricType;
use kuiper\metrics\metric\Timer;
use kuiper\metrics\metric\TimerInterface;

class MetricRegistry implements MetricRegistryInterface
{
    private array $metrics = [];

    public function __construct(protected readonly array $metricFilters = [])
    {
    }

    public function counter(string $name, array $tags = []): CounterInterface
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->registerOrNewMetric(new MetricId(MetricType::COUNTER, $name, $tags));
    }

    public function gauge(string $name, array $tags = []): GaugeInterface
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->registerOrNewMetric(new MetricId(MetricType::GAUGE, $name, $tags));
    }

    public function timer(string $name, array $tags = []): TimerInterface
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->registerOrNewMetric(new MetricId(MetricType::TIMER, $name, $tags));
    }

    public function metrics(): array
    {
        return Arrays::flatten($this->metrics);
    }

    protected function newCounter(MetricId $metricId): CounterInterface
    {
        return new Counter($metricId);
    }

    protected function newGauge(MetricId $metricId): GaugeInterface
    {
        return new Gauge($metricId);
    }

    protected function newTimer(MetricId $metricId): TimerInterface
    {
        return new Timer($metricId);
    }

    private function registerOrNewMetric(MetricId $metricId): MetricInterface
    {
        foreach ($this->metricFilters as $filter) {
            $metricId = $filter->map($metricId);
        }
        foreach ($this->metricFilters as $filter) {
            if (!$filter->accept($metricId)) {
                return match ($metricId->getType()) {
                    MetricType::COUNTER => Counter::create($metricId),
                    MetricType::GAUGE => Gauge::create($metricId),
                    MetricType::TIMER => Timer::create($metricId)
                };
            }
        }
        $metricInterface = match ($metricId->getType()) {
            MetricType::COUNTER => CounterInterface::class,
            MetricType::GAUGE => GaugeInterface::class,
            MetricType::TIMER => TimerInterface::class
        };
        if (isset($this->metrics[$metricId->getName()])) {
            $oldMetric = current($this->metrics[$metricId->getName()]);
            if (!($oldMetric instanceof $metricInterface)) {
                throw new InvalidArgumentException(sprintf('There is already a registered meter of a different type (%s vs. %s) with the same name: %s', $oldMetric->getMetricId()->getType()->value, $metricId->getType()->value, $metricId->getName()));
            }
        }
        $metricKey = (string) $metricId;
        if (!isset($this->metrics[$metricId->getName()][$metricKey])) {
            $this->metrics[$metricId->getName()][$metricKey] = match ($metricId->getType()) {
                MetricType::COUNTER => $this->newCounter($metricId),
                MetricType::GAUGE => $this->newGauge($metricId),
                MetricType::TIMER => $this->newTimer($metricId)
            };
        }

        return $this->metrics[$metricId->getName()][$metricKey];
    }
}
