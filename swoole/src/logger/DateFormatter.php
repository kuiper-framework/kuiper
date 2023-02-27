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

namespace kuiper\swoole\logger;

use DateTime;
use DateTimeZone;

class DateFormatter implements DateFormatterInterface
{
    /**
     * @var DateTimeZone
     */
    private readonly DateTimeZone $timezone;

    public function __construct(private readonly string $format, DateTimeZone $timeZone = null)
    {
        $this->timezone = $timeZone ?? new DateTimeZone(date_default_timezone_get());
    }

    /**
     * {@inheritDoc}
     */
    public function format(float $time): string
    {
        return DateTime::createFromFormat('U.u', sprintf('%.3f', $time))
            ->setTimezone($this->timezone)
            ->format($this->format);
    }
}
