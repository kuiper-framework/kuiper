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
    public function __construct(int $timeWindowSizeInSeconds)
    {
        $this->timeWindowSizeInSeconds = $timeWindowSizeInSeconds;
        $this->headIndex = 0;
        $epochSeconds = time();
        foreach (range(0, $this->timeWindowSizeInSeconds - 1) as $i) {
            $this->measurements[] = new PartialAggregation($epochSeconds);
        }
        $this->totalAggregation = new TotalAggregation();
    }

    public function record(int $duration, Outcome $outcome): Snapshot
    {
        $this->totalAggregation->record($duration, $outcome);
        $epochSeconds = time();
        $lastMeasurement = $this->measurements[$this->headIndex];
        $diffInSeconds = $epochSeconds - $lastMeasurement->getEpochSecond();
        if ($diffInSeconds > 0) {
            $secondsToMove = min($diffInSeconds, $this->timeWindowSizeInSeconds);
            do {
                --$secondsToMove;
                $this->headIndex = ($this->headIndex + 1) % $this->timeWindowSizeInSeconds;
                $lastMeasurement = $this->measurements[$this->headIndex];
                $this->totalAggregation->remove($lastMeasurement);
                $lastMeasurement->reset($epochSeconds - $secondsToMove);
            } while ($secondsToMove > 0);
        }
        $lastMeasurement->record($duration, $outcome);

        return new Snapshot($this->totalAggregation);
    }

    public function getSnapshot(): Snapshot
    {
        return new Snapshot($this->totalAggregation);
    }
}
