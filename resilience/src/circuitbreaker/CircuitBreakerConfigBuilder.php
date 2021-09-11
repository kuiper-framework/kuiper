<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

class CircuitBreakerConfigBuilder
{
    private const DEFAULT_WAIT_INTERVAL_IN_OPEN_STATE = 60000;

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
    private $slowCallDurationThreshold = 10000;
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

    /**
     * Configures an interval function which controls how long the CircuitBreaker should stay
     * open, before it switches to half open. The default interval function returns a fixed wait
     * duration of 60 seconds.
     *
     * @var callable
     */
    private $waitIntervalFunctionInOpenState;

    /**
     * Configures a Predicate which evaluates if the result of the protected function call
     * should be recorded as a failure and thus increase the failure rate.
     * The Predicate must return true if the result should count as a failure.
     * The Predicate must return false, if the result should count
     * as a success.
     *
     * @var callable|null
     */
    private $resultPredicate;

    /**
     * Configures a list of error classes that are ignored and thus neither count as a failure
     * nor success. Any exception matching or inheriting from one of the list will not count as
     * a failure nor success, even if marked via {@link #recordExceptions(Class[])} or {@link * #recordException(Predicate)}.
     *
     * @var string[]
     */
    private $ignoreExceptions = [];

    /**
     * Configures a Predicate which evaluates if an exception should be ignored and neither
     * count as a failure nor success. The Predicate must return true if the exception should be
     * ignored. The Predicate must return false, if the exception should count as a failure.
     *
     * @var callable|null
     */
    private $ignoreExceptionPredicate;

    /**
     * Configures a list of error classes that are recorded as a failure and thus increase the
     * failure rate. Any exception matching or inheriting from one of the list should count as a
     * failure, unless ignored via {@link ignoreExceptions()} or {@link ignoreException()}.
     *
     * @var string[]
     */
    private $recordExceptions = [];

    /**
     * Configures a list of error classes that are recorded as a failure and thus increase the
     * failure rate. Any exception matching or inheriting from one of the list should count as a
     * failure, unless ignored via {@link ignoreExceptions()} or {@link ignoreException()}.
     *
     * @var callable|null
     */
    private $recordExceptionPredicate;

    /**
     * CircuitBreakerConfigBuilder constructor.
     */
    public function __construct(?CircuitBreakerConfig $config = null)
    {
        if (null !== $config) {
            $this->failureRateThreshold = $config->getFailureRateThreshold();
            $this->slowCallRateThreshold = $config->getSlowCallRateThreshold();
            $this->slowCallDurationThreshold = $config->getSlowCallDurationThreshold();
            $this->permittedNumberOfCallsInHalfOpenState = $config->getPermittedNumberOfCallsInHalfOpenState();
            $this->maxWaitDurationInHalfOpenState = $config->getMaxWaitDurationInHalfOpenState();
            $this->slidingWindowType = $config->getSlidingWindowType();
            $this->slidingWindowSize = $config->getSlidingWindowSize();
            $this->minimumNumberOfCalls = $config->getMinimumNumberOfCalls();
            $this->waitIntervalFunctionInOpenState = $config->getWaitIntervalFunctionInOpenState();
            $this->resultPredicate = $config->getResultPredicate();
            $this->ignoreExceptions = $config->getIgnoreExceptions();
            $this->ignoreExceptionPredicate = $config->getIgnoreExceptionPredicate();
            $this->recordExceptions = $config->getRecordExceptions();
            $this->recordExceptionPredicate = $config->getRecordExceptionPredicate();
        } else {
            $this->slidingWindowType = SlideWindowType::COUNT_BASED();
            $this->setWaitIntervalInOpenState(self::DEFAULT_WAIT_INTERVAL_IN_OPEN_STATE);
        }
    }

    /**
     * @return float
     */
    public function getFailureRateThreshold()
    {
        return $this->failureRateThreshold;
    }

    /**
     * @return CircuitBreakerConfigBuilder
     */
    public function setFailureRateThreshold(float $failureRateThreshold): self
    {
        $this->failureRateThreshold = $failureRateThreshold;

        return $this;
    }

    /**
     * @return float
     */
    public function getSlowCallRateThreshold()
    {
        return $this->slowCallRateThreshold;
    }

    /**
     * @return CircuitBreakerConfigBuilder
     */
    public function setSlowCallRateThreshold(float $slowCallRateThreshold): self
    {
        $this->slowCallRateThreshold = $slowCallRateThreshold;

        return $this;
    }

    public function getSlowCallDurationThreshold(): int
    {
        return $this->slowCallDurationThreshold;
    }

    public function setSlowCallDurationThreshold(int $slowCallDurationThreshold): CircuitBreakerConfigBuilder
    {
        $this->slowCallDurationThreshold = $slowCallDurationThreshold;

        return $this;
    }

    public function getPermittedNumberOfCallsInHalfOpenState(): int
    {
        return $this->permittedNumberOfCallsInHalfOpenState;
    }

    public function setPermittedNumberOfCallsInHalfOpenState(int $permittedNumberOfCallsInHalfOpenState): CircuitBreakerConfigBuilder
    {
        $this->permittedNumberOfCallsInHalfOpenState = $permittedNumberOfCallsInHalfOpenState;

        return $this;
    }

    public function getMaxWaitDurationInHalfOpenState(): int
    {
        return $this->maxWaitDurationInHalfOpenState;
    }

    public function setMaxWaitDurationInHalfOpenState(int $maxWaitDurationInHalfOpenState): CircuitBreakerConfigBuilder
    {
        $this->maxWaitDurationInHalfOpenState = $maxWaitDurationInHalfOpenState;

        return $this;
    }

    public function getSlidingWindowType(): SlideWindowType
    {
        return $this->slidingWindowType;
    }

    public function setSlidingWindowType(SlideWindowType $slidingWindowType): CircuitBreakerConfigBuilder
    {
        $this->slidingWindowType = $slidingWindowType;

        return $this;
    }

    public function getSlidingWindowSize(): int
    {
        return $this->slidingWindowSize;
    }

    public function setSlidingWindowSize(int $slidingWindowSize): CircuitBreakerConfigBuilder
    {
        $this->slidingWindowSize = $slidingWindowSize;

        return $this;
    }

    public function getMinimumNumberOfCalls(): int
    {
        return $this->minimumNumberOfCalls;
    }

    public function setMinimumNumberOfCalls(int $minimumNumberOfCalls): CircuitBreakerConfigBuilder
    {
        $this->minimumNumberOfCalls = $minimumNumberOfCalls;

        return $this;
    }

    public function getWaitIntervalFunctionInOpenState(): callable
    {
        return $this->waitIntervalFunctionInOpenState;
    }

    public function setWaitIntervalInOpenState(int $interval): self
    {
        $this->waitIntervalFunctionInOpenState = static function () use ($interval): int {
            return $interval;
        };

        return $this;
    }

    public function setWaitIntervalFunctionInOpenState(callable $waitIntervalFunctionInOpenState): CircuitBreakerConfigBuilder
    {
        $this->waitIntervalFunctionInOpenState = $waitIntervalFunctionInOpenState;

        return $this;
    }

    public function getResultPredicate(): ?callable
    {
        return $this->resultPredicate;
    }

    public function setResultPredicate(callable $resultPredicate): CircuitBreakerConfigBuilder
    {
        $this->resultPredicate = $resultPredicate;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getIgnoreExceptions(): array
    {
        return $this->ignoreExceptions;
    }

    /**
     * @param string[] $ignoreExceptions
     */
    public function setIgnoreExceptions(array $ignoreExceptions): CircuitBreakerConfigBuilder
    {
        $this->ignoreExceptions = $ignoreExceptions;

        return $this;
    }

    public function getIgnoreExceptionPredicate(): ?callable
    {
        return $this->ignoreExceptionPredicate;
    }

    public function setIgnoreExceptionPredicate(callable $ignoreExceptionPredicate): CircuitBreakerConfigBuilder
    {
        $this->ignoreExceptionPredicate = $ignoreExceptionPredicate;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getRecordExceptions(): array
    {
        return $this->recordExceptions;
    }

    /**
     * @param string[] $recordExceptions
     */
    public function setRecordExceptions(array $recordExceptions): CircuitBreakerConfigBuilder
    {
        $this->recordExceptions = $recordExceptions;

        return $this;
    }

    public function getRecordExceptionPredicate(): ?callable
    {
        return $this->recordExceptionPredicate;
    }

    public function setRecordExceptionPredicate(callable $recordExceptionPredicate): CircuitBreakerConfigBuilder
    {
        $this->recordExceptionPredicate = $recordExceptionPredicate;

        return $this;
    }

    public function build(): CircuitBreakerConfig
    {
        return new CircuitBreakerConfig(
            $this->failureRateThreshold,
            $this->slowCallRateThreshold,
            $this->slowCallDurationThreshold,
            $this->permittedNumberOfCallsInHalfOpenState,
            $this->maxWaitDurationInHalfOpenState,
            $this->slidingWindowType,
            $this->slidingWindowSize,
            $this->minimumNumberOfCalls,
            $this->waitIntervalFunctionInOpenState,
            $this->resultPredicate,
            $this->ignoreExceptions,
            $this->ignoreExceptionPredicate,
            $this->recordExceptions,
            $this->recordExceptionPredicate
        );
    }
}
