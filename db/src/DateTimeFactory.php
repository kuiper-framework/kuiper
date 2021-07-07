<?php

declare(strict_types=1);

namespace kuiper\db;

use DateTimeInterface;

class DateTimeFactory implements DateTimeFactoryInterface
{
    public const TIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * {@inheritdoc}
     */
    public function currentTimeString(): string
    {
        return date(self::TIME_FORMAT);
    }

    /**
     * {@inheritdoc}
     */
    public function stringToTime(string $timeString): ?DateTimeInterface
    {
        return new \DateTime($timeString);
    }

    /**
     * {@inheritdoc}
     */
    public function timeToString(DateTimeInterface $time): string
    {
        return $time->format(self::TIME_FORMAT);
    }
}
