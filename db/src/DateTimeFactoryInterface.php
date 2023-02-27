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

namespace kuiper\db;

use DateTimeInterface;
use Exception;

interface DateTimeFactoryInterface
{
    /**
     * Returns current time string.
     */
    public function currentTimeString(): string;

    /**
     * Parses time string to time.
     *
     * @throws Exception
     */
    public function stringToTime(string $timeString): ?DateTimeInterface;

    /**
     * Formats time string.
     */
    public function timeToString(DateTimeInterface $time): string;
}
