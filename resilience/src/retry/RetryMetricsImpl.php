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

namespace kuiper\resilience\retry;

class RetryMetricsImpl implements RetryMetrics
{
    public function __construct(
        private readonly int $succeededAfterRetry,
        private readonly int $succeededWithoutRetry,
        private readonly int $failedAfterRetry,
        private readonly int $failedWithoutRetry)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getNumberOfSuccessfulCallsWithoutRetryAttempt(): int
    {
        return $this->succeededWithoutRetry;
    }

    /**
     * {@inheritDoc}
     */
    public function getNumberOfFailedCallsWithoutRetryAttempt(): int
    {
        return $this->failedWithoutRetry;
    }

    /**
     * {@inheritDoc}
     */
    public function getNumberOfSuccessfulCallsWithRetryAttempt(): int
    {
        return $this->succeededAfterRetry;
    }

    /**
     * {@inheritDoc}
     */
    public function getNumberOfFailedCallsWithRetryAttempt(): int
    {
        return $this->failedAfterRetry;
    }
}
