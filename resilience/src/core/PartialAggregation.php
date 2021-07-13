<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

class PartialAggregation extends AbstractAggregation
{
    /**
     * @var int
     */
    private $epochSecond;

    /**
     * PartialAggregation constructor.
     */
    public function __construct(int $epochSecond)
    {
        $this->epochSecond = $epochSecond;
    }

    public function getEpochSecond(): int
    {
        return $this->epochSecond;
    }

    public function reset(int $epochSecond): void
    {
        $this->epochSecond = $epochSecond;
        $this->totalDuration = 0;
        $this->totalNumberOfSlowCalls = 0;
        $this->totalNumberOfSlowFailedCalls = 0;
        $this->totalNumberOfFailedCalls = 0;
        $this->totalNumberOfCalls = 0;
    }
}
