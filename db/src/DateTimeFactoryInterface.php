<?php

declare(strict_types=1);

namespace kuiper\db;

interface DateTimeFactoryInterface
{
    /**
     * Returns current time string.
     */
    public function currentTimeString(): string;

    /**
     * Parses time string to time.
     */
    public function stringToTime(string $timeString): \DateTime;

    /**
     * Formats time string.
     */
    public function timeToString(\DateTime $time): string;
}
