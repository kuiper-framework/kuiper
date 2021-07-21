<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

class MockClock implements Clock
{
    /**
     * @var int
     */
    private $time;

    public function __construct()
    {
        $this->time = (int) (microtime(true) * 1000);
    }

    /**
     * {@inheritDoc}
     */
    public function getEpochSecond(): int
    {
        return (int) ($this->time / 1000);
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeInMillis(): int
    {
        return $this->time;
    }

    /**
     * {@inheritDoc}
     */
    public function sleep(int $millis): void
    {
        $this->time += $millis;
    }

    public function tick(int $millis): void
    {
        $this->time += $millis;
    }
}
