<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

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
}
