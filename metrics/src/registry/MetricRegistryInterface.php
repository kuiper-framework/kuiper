<?php

declare(strict_types=1);

namespace kuiper\metrics\registry;

use kuiper\metrics\metric\CounterInterface;
use kuiper\metrics\metric\GaugeInterface;
use kuiper\metrics\metric\MetricInterface;
use kuiper\metrics\metric\TimerInterface;

interface MetricRegistryInterface
{
    public function counter(string $name, array $tags = []): CounterInterface;

    public function gauge(string $name, array $tags = []): GaugeInterface;

    public function timer(string $name, array $tags = []): TimerInterface;

    /**
     * @return MetricInterface[]
     */
    public function metrics(): array;
}
