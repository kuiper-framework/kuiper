<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

class SimpleClock implements Clock
{
    public function getEpochSecond(): int
    {
        return time();
    }

    public function getTimeInMillis(): int
    {
        return (int) (microtime(true) * 1000);
    }

    public function sleep(int $millis): void
    {
        usleep($millis * 1000);
    }
}
