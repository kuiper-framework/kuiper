<?php

declare(strict_types=1);

namespace kuiper\db;

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
     *
     * @throws \Exception
     */
    public function stringToTime(string $timeString): \DateTime
    {
        return new \DateTime($timeString);
    }

    /**
     * {@inheritdoc}
     */
    public function timeToString(\DateTime $time): string
    {
        return $time->format(self::TIME_FORMAT);
    }
}
