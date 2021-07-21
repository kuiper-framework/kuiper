<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

class CircuitBreakerConfig
{
    /**
     * @var float
     */
    private $failureRateThreshold;
    /**
     * @var float
     */
    private $slowCallRateThreshold;
    /**
     * @var int
     */
    private $slowCallDurationThreshold;
    /**
     * @var int
     */
    private $permittedNumberOfCallsInHalfOpenState;
    /**
     * @var int
     */
    private $maxWaitDurationInHalfOpenState;

    /**
     * @var SlideWindowType
     */
    private $slidingWindowType;

    /**
     * @var int
     */
    private $slidingWindowSize;
    /**
     * @var int
     */
    private $minimumNumberOfCalls;

    /**
     * @var callable
     */
    private $waitIntervalFunctionInOpenState;

    /**
     * @var callable|null
     */
    private $resultPredicate;

    /**
     * @var string[]
     */
    private $ignoreExceptions;

    /**
     * @var callable|null
     */
    private $ignoreExceptionPredicate;

    /**
     * @var string[]
     */
    private $recordExceptions;

    /**
     * @var callable|null
     */
    private $recordExceptionPredicate;

    public function __construct(
        float $failureRateThreshold,
        float $slowCallRateThreshold,
        int $slowCallDurationThreshold,
        int $permittedNumberOfCallsInHalfOpenState,
        int $maxWaitDurationInHalfOpenState,
        SlideWindowType $slidingWindowType,
        int $slidingWindowSize,
        int $minimumNumberOfCalls,
        callable $waitIntervalFunctionInOpenState,
        ?callable $resultPredicate,
        array $ignoreExceptions,
        ?callable $ignoreExceptionPredicate,
        array $recordExceptions,
        ?callable $recordExceptionPredicate)
    {
        $this->failureRateThreshold = $failureRateThreshold;
        $this->slowCallRateThreshold = $slowCallRateThreshold;
        $this->slowCallDurationThreshold = $slowCallDurationThreshold;
        $this->permittedNumberOfCallsInHalfOpenState = $permittedNumberOfCallsInHalfOpenState;
        $this->maxWaitDurationInHalfOpenState = $maxWaitDurationInHalfOpenState;
        $this->slidingWindowType = $slidingWindowType;
        $this->slidingWindowSize = $slidingWindowSize;
        $this->minimumNumberOfCalls = $minimumNumberOfCalls;
        $this->waitIntervalFunctionInOpenState = $waitIntervalFunctionInOpenState;
        $this->resultPredicate = $resultPredicate;
        $this->ignoreExceptions = $ignoreExceptions;
        $this->ignoreExceptionPredicate = $ignoreExceptionPredicate;
        $this->recordExceptions = $recordExceptions;
        $this->recordExceptionPredicate = $recordExceptionPredicate;
    }

    /**
     * return true if the result should count as a failure.
     *
     * @param mixed $result
     */
    public function isFailureResult($result): bool
    {
        if (null === $this->resultPredicate) {
            return false;
        }

        return call_user_func($this->resultPredicate, $result);
    }

    public function shouldIgnoreException(\Exception $exception): bool
    {
        foreach ($this->ignoreExceptions as $type) {
            if ($exception instanceof $type) {
                return true;
            }
        }
        if (null !== $this->ignoreExceptionPredicate) {
            return call_user_func($this->ignoreExceptionPredicate, $exception);
        }

        return false;
    }

    public function isFailureException(\Exception $exception): bool
    {
        foreach ($this->recordExceptions as $type) {
            if ($exception instanceof $type) {
                return true;
            }
        }
        if (null !== $this->recordExceptionPredicate) {
            return call_user_func($this->recordExceptionPredicate, $exception);
        }

        return true;
    }

    public function getWaitIntervalInOpenState(int $attempts): int
    {
        return call_user_func($this->waitIntervalFunctionInOpenState, $attempts);
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

    public function getSlidingWindowType(): SlideWindowType
    {
        return $this->slidingWindowType;
    }

    public function getSlidingWindowSize(): int
    {
        return $this->slidingWindowSize;
    }

    public function getMinimumNumberOfCalls(): int
    {
        return $this->minimumNumberOfCalls;
    }

    public function getWaitIntervalFunctionInOpenState(): callable
    {
        return $this->waitIntervalFunctionInOpenState;
    }

    public function getResultPredicate(): ?callable
    {
        return $this->resultPredicate;
    }

    /**
     * @return string[]
     */
    public function getIgnoreExceptions(): array
    {
        return $this->ignoreExceptions;
    }

    public function getIgnoreExceptionPredicate(): ?callable
    {
        return $this->ignoreExceptionPredicate;
    }

    /**
     * @return string[]
     */
    public function getRecordExceptions(): array
    {
        return $this->recordExceptions;
    }

    public function getRecordExceptionPredicate(): ?callable
    {
        return $this->recordExceptionPredicate;
    }

    public static function builder(?CircuitBreakerConfig $config = null): CircuitBreakerConfigBuilder
    {
        return new CircuitBreakerConfigBuilder($config);
    }

    public static function ofDefaults(): CircuitBreakerConfig
    {
        static $default;
        if (null === $default) {
            $default = self::builder()->build();
        }

        return $default;
    }
}
