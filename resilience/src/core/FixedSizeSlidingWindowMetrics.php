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

class FixedSizeSlidingWindowMetrics implements Metrics
{
    private int $headIndex = 0;

    private readonly TotalAggregation $totalAggregation;

    /**
     * @var Measurement[]
     */
    private array $measurements = [];

    /**
     * FixedSizeSlidingWindowMetrics constructor.
     */
    public function __construct(
        string $name,
        private readonly int $windowSize,
        CounterFactory $counterFactory)
    {
        foreach (range(0, $this->windowSize - 1) as $i) {
            $prefix = $name.'_'.$i;
            $this->measurements[] = new Measurement(
                $counterFactory->create($prefix.'.duration'),
                $counterFactory->create($prefix.'.slow_calls'),
                $counterFactory->create($prefix.'.slow_failed_calls'),
                $counterFactory->create($prefix.'.failed_calls'),
                $counterFactory->create($prefix.'.calls')
            );
        }
        $this->totalAggregation = new TotalAggregation(
            $counterFactory->create($name.'.duration'),
            $counterFactory->create($name.'.slow_calls'),
            $counterFactory->create($name.'.slow_failed_calls'),
            $counterFactory->create($name.'.failed_calls'),
            $counterFactory->create($name.'.calls')
        );
    }

    public function record(int $duration, Outcome $outcome): Snapshot
    {
        $bucket = new EphemeralMeasure();
        $bucket->record($duration, $outcome);
        $this->headIndex = ($this->headIndex + 1) % $this->windowSize;
        $lastMeasurement = $this->measurements[$this->headIndex];
        $bucket->remove($lastMeasurement);
        $lastMeasurement->reset();
        $lastMeasurement->record($duration, $outcome);
        $this->totalAggregation->aggregate($bucket);

        return Snapshot::fromAggregation($this->totalAggregation);
    }

    public function reset(): void
    {
        $this->totalAggregation->reset();
        foreach ($this->measurements as $measurement) {
            $measurement->reset();
        }
    }

    public function getSnapshot(): Snapshot
    {
        return Snapshot::fromAggregation($this->totalAggregation);
    }

    public function getWindowSize(): int
    {
        return $this->windowSize;
    }

    public function getWindowType(): SlideWindowType
    {
        return SlideWindowType::COUNT_BASED;
    }
}
