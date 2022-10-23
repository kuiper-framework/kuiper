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

class Snapshot
{
    public function __construct(
        private readonly int $duration,
        private readonly int $numberOfCalls,
        private readonly int $numberOfSlowCalls,
        private readonly int $numberOfFailedCalls,
        private readonly int $numberOfSlowFailedCalls)
    {
    }

    public static function fromAggregation(TotalAggregation $aggregation): self
    {
        return new self(
            $aggregation->getTotalDuration(),
            $aggregation->getTotalNumberOfCalls(),
            $aggregation->getTotalNumberOfSlowCalls(),
            $aggregation->getTotalNumberOfFailedCalls(),
            $aggregation->getTotalNumberOfSlowFailedCalls()
        );
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getNumberOfSlowCalls(): int
    {
        return $this->numberOfSlowCalls;
    }

    public function getNumberOfSlowFailedCalls(): int
    {
        return $this->numberOfSlowFailedCalls;
    }

    public function getNumberOfFailedCalls(): int
    {
        return $this->numberOfFailedCalls;
    }

    public function getNumberOfCalls(): int
    {
        return $this->numberOfCalls;
    }

    public function getNumberOfSuccessfulCalls(): int
    {
        return $this->numberOfCalls - $this->numberOfFailedCalls;
    }

    public function getNumberOfSlowSuccessfulCalls(): int
    {
        return $this->numberOfSlowCalls - $this->numberOfSlowFailedCalls;
    }

    public static function dummy(): Snapshot
    {
        static $dummy;
        if (null === $dummy) {
            $dummy = new self(0, 0, 0, 0, 0);
        }

        return $dummy;
    }
}
