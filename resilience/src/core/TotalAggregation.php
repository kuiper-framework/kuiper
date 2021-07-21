<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

class TotalAggregation extends AbstractAggregation
{
    public function aggregate(EphemeralMeasure $bucket): void
    {
        $this->totalDuration->increment($bucket->getTotalDuration());
        $this->totalNumberOfSlowCalls->increment($bucket->getTotalNumberOfSlowCalls());
        $this->totalNumberOfSlowFailedCalls->increment($bucket->getTotalNumberOfSlowFailedCalls());
        $this->totalNumberOfFailedCalls->increment($bucket->getTotalNumberOfFailedCalls());
        $this->totalNumberOfCalls->increment($bucket->getTotalNumberOfCalls());
    }
}
