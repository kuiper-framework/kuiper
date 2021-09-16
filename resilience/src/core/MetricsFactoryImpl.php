<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\resilience\core;

use kuiper\resilience\circuitbreaker\SlideWindowType;

class MetricsFactoryImpl implements MetricsFactory
{
    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var CounterFactory
     */
    private $counterFactory;

    /**
     * @var array
     */
    private $metrics;

    /**
     * MetricsFactoryImpl constructor.
     */
    public function __construct(Clock $clock, CounterFactory $counterFactory)
    {
        $this->clock = $clock;
        $this->counterFactory = $counterFactory;
    }

    public function create(string $name, SlideWindowType $type, int $windowSize): Metrics
    {
        if (isset($this->metrics[$name])) {
            if ($this->metrics[$name]['type'] !== $type->value || $this->metrics[$name]['size'] !== $windowSize) {
                unset($this->metrics[$name]);
            } else {
                return $this->metrics[$name]['metrics'];
            }
        }
        if (SlideWindowType::COUNT_BASED === $type->value) {
            $metrics = new SlidingTimeWindowMetrics($name, $windowSize, $this->clock, $this->counterFactory);
        } else {
            $metrics = new FixedSizeSlidingWindowMetrics($name, $windowSize, $this->counterFactory);
        }
        $this->metrics[$name] = [
            'type' => $type,
            'size' => $windowSize,
            'metrics' => $metrics,
        ];

        return $metrics;
    }
}
