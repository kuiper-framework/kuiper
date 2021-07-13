<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

class FixedSizeSlidingWindowMetrics implements Metrics
{
    /**
     * @var int
     */
    private $windowSize;

    /**
     * @var Measurement[]
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
    public function __construct(int $windowSize)
    {
        $this->windowSize = $windowSize;
        $this->headIndex = 0;
        foreach (range(0, $this->windowSize - 1) as $i) {
            $this->measurements[] = new Measurement();
        }
        $this->totalAggregation = new TotalAggregation();
    }

    public function record(int $duration, Outcome $outcome): Snapshot
    {
        $this->totalAggregation->record($duration, $outcome);
        $this->headIndex = ($this->headIndex + 1) % $this->windowSize;
        $bucket = $this->measurements[$this->headIndex];
        $this->totalAggregation->remove($bucket);
        $bucket->reset();
        $bucket->record($duration, $outcome);

        return new Snapshot($this->totalAggregation);
    }

    public function getSnapshot(): Snapshot
    {
        return new Snapshot($this->totalAggregation);
    }
}
