<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

class SlidingTimeWindowMetrics implements Metrics
{
    /**
     * @var int
     */
    private $timeWindowSizeInSeconds;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var PartialAggregation[]
     */
    private $measurements;

    /**
     * @var TotalAggregation
     */
    private $totalAggregation;

    /**
     * @var int
     */
    private $headIndex;

    /**
     * FixedSizeSlidingWindowMetrics constructor.
     */
    public function __construct(string $name, int $timeWindowSizeInSeconds, Clock $clock, CounterFactory $counterFactory)
    {
        $this->timeWindowSizeInSeconds = $timeWindowSizeInSeconds;
        $this->headIndex = 0;
        $this->clock = $clock;
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

        return new Snapshot($this->totalAggregation);
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
        return new Snapshot($this->totalAggregation);
    }
}
