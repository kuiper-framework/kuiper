<?php

declare(strict_types=1);

namespace kuiper\db;

use Carbon\Carbon;

class CarbonDateTimeFactory implements DateTimeFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function currentTimeString(): string
    {
        return Carbon::now()->toDateTimeString();
    }

    /**
     * {@inheritdoc}
     */
    public function stringToTime(string $timeString): \DateTime
    {
        return Carbon::parse($timeString);
    }

    /**
     * {@inheritdoc}
     */
    public function timeToString(\DateTime $time): string
    {
        return Carbon::instance($time)->toDateTimeString();
    }
}
