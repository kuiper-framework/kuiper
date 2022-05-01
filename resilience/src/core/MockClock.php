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

class MockClock implements Clock
{
    private int $time;

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
