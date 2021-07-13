<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

interface CircuitBreakerMetrics
{
    /**
     * Returns the current failure rate in percentage. If the number of measured calls is below
     * the minimum number of measured calls, it returns -1.
     *
     * @return float the failure rate in percentage
     */
    public function getFailureRate(): float;

    /**
     * Returns the current percentage of calls which were slower than a certain threshold. If
     * the number of measured calls is below the minimum number of measured calls, it returns
     * -1.
     *
     * @return float the failure rate in percentage
     */
    public function getSlowCallRate(): float;

    /**
     * Returns the current total number of calls which were slower than a certain threshold.
     *
     * @return int the current total number of calls which were slower than a certain threshold
     */
    public function getNumberOfSlowCalls(): int;

    /**
     * Returns the current number of successful calls which were slower than a certain
     * threshold.
     *
     * @return int the current number of successful calls which were slower than a certain threshold
     */
    public function getNumberOfSlowSuccessfulCalls(): int;

    /**
     * Returns the current number of failed calls which were slower than a certain threshold.
     *
     * @return int the current number of failed calls which were slower than a certain threshold
     */
    public function getNumberOfSlowFailedCalls(): int;

    /**
     * Returns the current total number of buffered calls in the ring buffer.
     *
     * @return int the current total number of buffered calls in the ring buffer
     */
    public function getNumberOfBufferedCalls(): int;

    /**
     * Returns the current number of failed buffered calls in the ring buffer.
     *
     * @return int the current number of failed buffered calls in the ring buffer
     */
    public function getNumberOfFailedCalls(): int;

    /**
     * Returns the current number of not permitted calls, when the state is OPEN.
     * <p>
     * The number of denied calls is always 0, when the CircuitBreaker state is CLOSED or
     * HALF_OPEN. The number of denied calls is only increased when the CircuitBreaker state is
     * OPEN.
     *
     * @return int the current number of not permitted calls
     */
    public function getNumberOfNotPermittedCalls(): int;

    /**
     * Returns the current number of successful buffered calls in the ring buffer.
     *
     * @return int the current number of successful buffered calls in the ring buffer
     */
    public function getNumberOfSuccessfulCalls(): int;
}
