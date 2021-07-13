<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

class Measurement extends AbstractAggregation
{
    public function reset(): void
    {
        $this->totalDuration = 0;
        $this->totalNumberOfSlowCalls = 0;
        $this->totalNumberOfSlowFailedCalls = 0;
        $this->totalNumberOfFailedCalls = 0;
        $this->totalNumberOfCalls = 0;
    }
}
