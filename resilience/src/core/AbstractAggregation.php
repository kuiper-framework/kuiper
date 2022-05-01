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

abstract class AbstractAggregation
{
    /**
     * TotalAggregation constructor.
     */
    public function __construct(
        protected readonly Counter $totalDuration,
        protected readonly Counter $totalNumberOfSlowCalls,
        protected readonly Counter $totalNumberOfSlowFailedCalls,
        protected readonly Counter $totalNumberOfFailedCalls,
        protected readonly Counter $totalNumberOfCalls)
    {
    }

    public function record(int $duration, Outcome $outcome): void
    {
        $this->totalNumberOfCalls->increment();
        $this->totalDuration->increment($duration);
        switch ($outcome) {
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

            default:
                // pass
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
