<?php

declare(strict_types=1);

namespace kuiper\db;

use Carbon\Carbon;
use DateTimeInterface;

class CarbonDateTimeFactory extends DateTimeFactory
{
    /**
     * {@inheritdoc}
     */
    public function stringToTime(string $timeString): ?DateTimeInterface
    {
        return Carbon::parse($timeString);
    }
}
