<?php

declare(strict_types=1);

namespace kuiper\metrics;

use InvalidArgumentException;
use kuiper\metrics\metric\CounterInterface;
use kuiper\metrics\metric\GaugeInterface;
use kuiper\metrics\metric\TimerInterface;
use kuiper\metrics\registry\MetricRegistryInterface;

class Metrics
{
    private static MetricRegistryInterface $REGISTRY;

    public static function counter(string $name, array $tags = []): CounterInterface
    {
        return self::getDefaultRegistry()->counter($name, $tags);
    }

    public static function gauge(string $name, array $tags = []): GaugeInterface
    {
        return self::getDefaultRegistry()->gauge($name, $tags);
    }

    public static function timer(string $name, array $tags = []): TimerInterface
    {
        return self::getDefaultRegistry()->timer($name, $tags);
    }

    public static function getDefaultRegistry(): MetricRegistryInterface
    {
        if (null === self::$REGISTRY) {
            throw new InvalidArgumentException('Please set default registry');
        }

        return self::$REGISTRY;
    }

    public static function setDefaultRegistry(MetricRegistryInterface $registry): void
    {
        self::$REGISTRY = $registry;
    }
}
