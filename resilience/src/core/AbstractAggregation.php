<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

abstract class AbstractAggregation
{
    /**
     * @var Counter
     */
    protected $totalDuration;
    /**
     * @var Counter
     */
    protected $totalNumberOfSlowCalls;
    /**
     * @var Counter
     */
    protected $totalNumberOfSlowFailedCalls;
    /**
     * @var Counter
     */
    protected $totalNumberOfFailedCalls;
    /**
     * @var Counter
     */
    protected $totalNumberOfCalls;

    /**
     * TotalAggregation constructor.
     */
    public function __construct(
        Counter $totalDuration,
        Counter $totalNumberOfSlowCalls,
        Counter $totalNumberOfSlowFailedCalls,
        Counter $totalNumberOfFailedCalls,
        Counter $totalNumberOfCalls)
    {
        $this->totalDuration = $totalDuration;
        $this->totalNumberOfSlowCalls = $totalNumberOfSlowCalls;
        $this->totalNumberOfSlowFailedCalls = $totalNumberOfSlowFailedCalls;
        $this->totalNumberOfFailedCalls = $totalNumberOfFailedCalls;
        $this->totalNumberOfCalls = $totalNumberOfCalls;
    }

    public function record(int $duration, Outcome $outcome): void
    {
        $this->totalNumberOfCalls->increment();
        $this->totalDuration->increment($duration);
        switch ($outcome->value) {
            case Outcome::SLOW_SUCCESS:
                $this->totalNumberOfSlowCalls->increment();
                break;

            case Outcome::SLOW_ERROR:
                $this->totalNumberOfSlowCalls->increment();
                $this->totalNumberOfFailedCalls->increment();
                $this->totalNumberOfSlowFailedCalls->increment();
                break;

            case Outcome::ERROR:
                $this->totalNumberOfFailedCalls->increment();
                break;
        }
    }

    public function reset(): void
    {
        $this->totalDuration->set(0);
        $this->totalNumberOfSlowCalls->set(0);
        $this->totalNumberOfSlowFailedCalls->set(0);
        $this->totalNumberOfFailedCalls->set(0);
        $this->totalNumberOfCalls->set(0);
    }

    public function getTotalDuration(): int
    {
        return $this->totalDuration->get();
    }

    public function getTotalNumberOfSlowCalls(): int
    {
        return $this->totalNumberOfSlowCalls->get();
    }

    public function getTotalNumberOfSlowFailedCalls(): int
    {
        return $this->totalNumberOfSlowFailedCalls->get();
    }

    public function getTotalNumberOfFailedCalls(): int
    {
        return $this->totalNumberOfFailedCalls->get();
    }

    public function getTotalNumberOfCalls(): int
    {
        return $this->totalNumberOfCalls->get();
    }
}
