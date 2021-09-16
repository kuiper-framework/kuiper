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
    /**
     * @var int
     */
    private $duration;
    /**
     * @var int
     */
    private $numberOfSlowCalls;
    /**
     * @var int
     */
    private $numberOfSlowFailedCalls;
    /**
     * @var int
     */
    private $numberOfFailedCalls;
    /**
     * @var int
     */
    private $numberOfCalls;

    public function __construct(TotalAggregation $aggregation = null)
    {
        if (null !== $aggregation) {
            $this->duration = $aggregation->getTotalDuration();
            $this->numberOfSlowCalls = $aggregation->getTotalNumberOfSlowCalls();
            $this->numberOfFailedCalls = $aggregation->getTotalNumberOfFailedCalls();
            $this->numberOfSlowFailedCalls = $aggregation->getTotalNumberOfSlowFailedCalls();
            $this->numberOfCalls = $aggregation->getTotalNumberOfCalls();
        } else {
            $this->duration = 0;
            $this->numberOfSlowCalls = 0;
            $this->numberOfFailedCalls = 0;
            $this->numberOfSlowFailedCalls = 0;
            $this->numberOfCalls = 0;
        }
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
            $dummy = new self();
        }

        return $dummy;
    }
}
