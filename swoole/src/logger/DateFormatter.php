<?php

declare(strict_types=1);

namespace kuiper\swoole\logger;

use DateTimeZone;

class DateFormatter implements DateFormatterInterface
{
    /**
     * @var string
     */
    private $format;

    /**
     * @var DateTimeZone
     */
    private $timezone;

    /**
     * DateFormatter constructor.
     *
     * @param string            $format
     * @param DateTimeZone|null $timeZone
     */
    public function __construct(string $format, DateTimeZone $timeZone = null)
    {
        $this->format = $format;
        $this->timezone = $timeZone ?? new DateTimeZone(date_default_timezone_get());
    }

    /**
     * {@inheritDoc}
     */
    public function format(float $time): string
    {
        return \DateTime::createFromFormat('U.u', (string) $time)
            ->setTimezone($this->timezone)
            ->format($this->format);
    }
}
