<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

class EphemeralMeasure
{
    /**
     * @var int
     */
    protected $totalDuration = 0;
    /**
     * @var int
     */
    protected $totalNumberOfSlowCalls = 0;
    /**
     * @var int
     */
    protected $totalNumberOfSlowFailedCalls = 0;
    /**
     * @var int
     */
    protected $totalNumberOfFailedCalls = 0;
    /**
     * @var int
     */
    protected $totalNumberOfCalls = 0;

    public function record(int $duration, Outcome $outcome): void
    {
        ++$this->totalNumberOfCalls;
        $this->totalDuration += $duration;
        switch ($outcome->value) {
            case Outcome::SLOW_SUCCESS:
                $this->totalNumberOfSlowCalls++;
                break;

            case Outcome::SLOW_ERROR:
                $this->totalNumberOfSlowCalls++;
                ++$this->totalNumberOfFailedCalls;
                ++$this->totalNumberOfSlowFailedCalls;
                break;

            case Outcome::ERROR:
                $this->totalNumberOfFailedCalls++;
                break;
        }
    }

    public function reset(): void
    {
        $this->totalDuration = 0;
        $this->totalNumberOfSlowCalls = 0;
        $this->totalNumberOfSlowFailedCalls = 0;
        $this->totalNumberOfFailedCalls = 0;
        $this->totalNumberOfCalls = 0;
    }

    public function remove(AbstractAggregation $bucket): void
    {
        $this->totalDuration -= $bucket->getTotalDuration();
        $this->totalNumberOfSlowCalls -= $bucket->getTotalNumberOfSlowCalls();
        $this->totalNumberOfSlowFailedCalls -= $bucket->getTotalNumberOfSlowFailedCalls();
        $this->totalNumberOfFailedCalls -= $bucket->getTotalNumberOfFailedCalls();
        $this->totalNumberOfCalls -= $bucket->getTotalNumberOfCalls();
    }

    public function getTotalDuration(): int
    {
        return $this->totalDuration;
    }

    public function getTotalNumberOfSlowCalls(): int
    {
        return $this->totalNumberOfSlowCalls;
    }

    public function getTotalNumberOfSlowFailedCalls(): int
    {
        return $this->totalNumberOfSlowFailedCalls;
    }

    public function getTotalNumberOfFailedCalls(): int
    {
        return $this->totalNumberOfFailedCalls;
    }

    public function getTotalNumberOfCalls(): int
    {
        return $this->totalNumberOfCalls;
    }
}
