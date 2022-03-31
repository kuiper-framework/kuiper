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

use kuiper\resilience\circuitbreaker\SlideWindowType;

interface Metrics
{
    /**
     * Records a call.
     *
     * @param int     $duration the duration of the call
     * @param Outcome $outcome  the outcome of the call
     */
    public function record(int $duration, Outcome $outcome): Snapshot;

    /**
     * Reset metric.
     */
    public function reset(): void;

    /**
     * Returns a snapshot.
     *
     * @return Snapshot a snapshot
     */
    public function getSnapshot(): Snapshot;

    /**
     * @return int
     */
    public function getWindowSize(): int;

    /**
     * @return SlideWindowType
     */
    public function getWindowType(): SlideWindowType;
}
