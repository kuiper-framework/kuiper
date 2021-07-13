<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

class CircuitBreakerConfig
{
    /**
     * Configures the failure rate threshold in percentage.
     * When the failure rate is equal or greater than the threshold, the CircuitBreaker transitions
     * to open and starts short-circuiting calls.
     *
     * @var float
     */
    private $failureRateThreshold = 50;
    /**
     * Configures a threshold in percentage.
     * The CircuitBreaker considers a call as slow when the call duration is greater than slowCallDurationThreshold.
     * When the percentage of slow calls is equal or greater the threshold, the CircuitBreaker transitions
     * to open and starts short-circuiting calls.
     *
     * @var float
     */
    private $slowCallRateThreshold = 100;
    /**
     * Configures the duration threshold above which calls are considered as slow and increase the rate of slow calls.
     *
     * @var int
     */
    private $slowCallDurationThreshold = 60000;
    /**
     * Configures the number of permitted calls when the CircuitBreaker is half open.
     *
     * @var int
     */
    private $permittedNumberOfCallsInHalfOpenState = 10;
    /**
     * Configures a maximum wait duration which controls the longest amount of time a CircuitBreaker
     * could stay in Half Open state, before it switches to open.
     * Value 0 means Circuit Breaker would wait infinitely in HalfOpen State until all permitted calls have been completed.
     *
     * @var int
     */
    private $maxWaitDurationInHalfOpenState = 0;

    /**
     * Configures the type of the sliding window which is used to record the outcome of calls when the CircuitBreaker is closed.
     * If the sliding window is COUNT_BASED, the last slidingWindowSize calls are recorded and aggregated.
     * If the sliding window is TIME_BASED, the calls of the last slidingWindowSize seconds recorded and aggregated.
     *
     * @var SlideWindowType
     */
    private $slidingWindowType;

    /**
     * Configures the size of the sliding window which is used to record the outcome of calls when the CircuitBreaker is closed.
     *
     * @var int
     */
    private $slidingWindowSize = 100;
    /**
     * Configures the minimum number of calls which are required (per sliding window period) before the CircuitBreaker
     * can calculate the error rate or slow call rate.
     * For example, if minimumNumberOfCalls is 10, then at least 10 calls must be recorded, before the failure rate
     * can be calculated. If only 9 calls have been recorded the CircuitBreaker will not transition to open even if
     * all 9 calls have failed.
     *
     * @var int
     */
    private $minimumNumberOfCalls = 100;

    public function validateResult($result): bool
    {
    }

    public function shouldIgnoreException(\Exception $exception): bool
    {
    }

    public function isFailureException(\Exception $exception): bool
    {
    }

    public function getFailureRateThreshold(): float
    {
        return $this->failureRateThreshold;
    }

    public function getSlowCallRateThreshold(): float
    {
        return $this->slowCallRateThreshold;
    }

    public function getSlowCallDurationThreshold(): int
    {
        return $this->slowCallDurationThreshold;
    }

    public function getPermittedNumberOfCallsInHalfOpenState(): int
    {
        return $this->permittedNumberOfCallsInHalfOpenState;
    }

    public function getMaxWaitDurationInHalfOpenState(): int
    {
        return $this->maxWaitDurationInHalfOpenState;
    }

    public function getMinimumNumberOfCalls(): int
    {
        return $this->minimumNumberOfCalls;
    }

    public function getSlidingWindowType(): SlideWindowType
    {
        return $this->slidingWindowType;
    }

    public function getSlidingWindowSize(): int
    {
        return $this->slidingWindowSize;
    }

    /**
     * Returns an interval function which controls how long the CircuitBreaker should stay open,
     * before it switches to half open.
     *
     * @return callable the CircuitBreakerConfig.Builder
     */
    public function getWaitIntervalFunctionInOpenState(): callable
    {
        return $this->waitIntervalFunctionInOpenState;
    }

    public function getRecordExceptionPredicate(): callable
    {
        return $this->recordExceptionPredicate;
    }

    public function getRecordResultPredicate(): callable
    {
        return $this->recordResultPredicate;
    }

    public function getIgnoreExceptionPredicate(): callable
    {
        return $this->ignoreExceptionPredicate;
    }

    public function isAutomaticTransitionFromOpenToHalfOpenEnabled(): bool
    {
        return $this->automaticTransitionFromOpenToHalfOpenEnabled;
    }
}
