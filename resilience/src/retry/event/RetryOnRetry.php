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

namespace kuiper\resilience\retry\event;

use kuiper\resilience\retry\Retry;
use Throwable;

/**
 * 发生重试
 * Class RetryOnRetry.
 */
class RetryOnRetry
{
    public function __construct(
        private readonly Retry $retry,
        private readonly int $numOfAttempts,
        private readonly int $interval,
        private readonly ?Throwable $lastException,
        private readonly mixed $result)
    {
    }

    public function getRetry(): Retry
    {
        return $this->retry;
    }

    public function getNumOfAttempts(): int
    {
        return $this->numOfAttempts;
    }

    public function getInterval(): int
    {
        return $this->interval;
    }

    public function getLastException(): ?Throwable
    {
        return $this->lastException;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }
}
