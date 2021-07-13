<?php

declare(strict_types=1);

namespace kuiper\resilience\retry;

class RetryMetricsImpl implements RetryMetrics
{
    /**
     * @var int
     */
    private $succeededAfterRetry;
    /**
     * @var int
     */
    private $succeededWithoutRetry;

    /**
     * @var int
     */
    private $failedAfterRetry;

    /**
     * @var int
     */
    private $failedWithoutRetry;

    /**
     * RetryMetricsImpl constructor.
     */
    public function __construct(int $succeededAfterRetry, int $succeededWithoutRetry, int $failedAfterRetry, int $failedWithoutRetry)
    {
        $this->succeededAfterRetry = $succeededAfterRetry;
        $this->succeededWithoutRetry = $succeededWithoutRetry;
        $this->failedAfterRetry = $failedAfterRetry;
        $this->failedWithoutRetry = $failedWithoutRetry;
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
