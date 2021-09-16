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
