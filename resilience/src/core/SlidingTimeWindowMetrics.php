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

class SlidingTimeWindowMetrics implements Metrics
{
    /**
     * @var int
     */
    private int $headIndex = 0;

    /**
     * @var PartialAggregation[]
     */
    private array $measurements;

    /**
     * @var TotalAggregation
     */
    private readonly TotalAggregation $totalAggregation;

    /**
     * FixedSizeSlidingWindowMetrics constructor.
     */
    public function __construct(
        string $name,
        private readonly int $timeWindowSizeInSeconds,
        private readonly Clock $clock,
        CounterFactory $counterFactory)
    {
        $epochSecond = $clock->getEpochSecond();
        foreach (range(0, $this->timeWindowSizeInSeconds - 1) as $i) {
            $prefix = $name.'_'.$i;
            $epochSecondCounter = $counterFactory->create($prefix.'.time');
            $epochSecondCounter->set($epochSecond);
            $this->measurements[] = new PartialAggregation(
                $counterFactory->create($prefix.'.duration'),
                $counterFactory->create($prefix.'.slow_calls'),
                $counterFactory->create($prefix.'.slow_failed_calls'),
                $counterFactory->create($prefix.'.failed_calls'),
                $counterFactory->create($prefix.'.calls'),
                $epochSecondCounter
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

    /**
     * {@inheritDoc}
     */
    public function record(int $duration, Outcome $outcome): Snapshot
    {
        $bucket = new EphemeralMeasure();
        $bucket->record($duration, $outcome);
        $epochSeconds = $this->clock->getEpochSecond();
        $lastMeasurement = $this->measurements[$this->headIndex];
        $diffInSeconds = $epochSeconds - $lastMeasurement->getEpochSecond();
        if ($diffInSeconds > 0) {
            $secondsToMove = min($diffInSeconds, $this->timeWindowSizeInSeconds);
            do {
                --$secondsToMove;
                $this->headIndex = ($this->headIndex + 1) % $this->timeWindowSizeInSeconds;
                $lastMeasurement = $this->measurements[$this->headIndex];
                $bucket->remove($lastMeasurement);
                $lastMeasurement->setEpochSecond($epochSeconds - $secondsToMove);
                $lastMeasurement->reset();
            } while ($secondsToMove > 0);
        }
        $lastMeasurement->record($duration, $outcome);
        $this->totalAggregation->aggregate($bucket);

        return Snapshot::fromAggregation($this->totalAggregation);
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->totalAggregation->reset();

        $epochSecond = $this->clock->getEpochSecond();
        foreach ($this->measurements as $measurement) {
            $measurement->setEpochSecond($epochSecond);
            $measurement->reset();
        }
    }

    public function getSnapshot(): Snapshot
    {
        return Snapshot::fromAggregation($this->totalAggregation);
    }

    public function getWindowSize(): int
    {
        return $this->timeWindowSizeInSeconds;
    }

    public function getWindowType(): SlideWindowType
    {
        return SlideWindowType::TIME_BASED;
    }
}
