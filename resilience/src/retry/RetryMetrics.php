<?php

declare(strict_types=1);

namespace kuiper\resilience\retry;

interface RetryMetrics
{
    /**
     * Returns the number of successful calls without a retry attempt.
     *
     * @return int the number of successful calls without a retry attempt
     */
    public function getNumberOfSuccessfulCallsWithoutRetryAttempt(): int;

    /**
     * Returns the number of failed calls without a retry attempt.
     *
     * @return int the number of failed calls without a retry attempt
     */
    public function getNumberOfFailedCallsWithoutRetryAttempt(): int;

    /**
     * Returns the number of successful calls after a retry attempt.
     *
     * @return int the number of successful calls after a retry attempt
     */
    public function getNumberOfSuccessfulCallsWithRetryAttempt(): int;

    /**
     * Returns the number of failed calls after all retry attempts.
     *
     * @return int the number of failed calls after all retry attempts
     */
    public function getNumberOfFailedCallsWithRetryAttempt(): int;
}
