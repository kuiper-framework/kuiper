<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

class TotalAggregation extends AbstractAggregation
{
    public function remove(AbstractAggregation $bucket): void
    {
        $this->totalDuration -= $bucket->totalDuration;
        $this->totalNumberOfSlowCalls -= $bucket->totalNumberOfSlowCalls;
        $this->totalNumberOfSlowFailedCalls -= $bucket->totalNumberOfSlowFailedCalls;
        $this->totalNumberOfFailedCalls -= $bucket->totalNumberOfFailedCalls;
        $this->totalNumberOfCalls -= $bucket->totalNumberOfCalls;
    }
}
