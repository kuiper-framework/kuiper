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

namespace kuiper\resilience\circuitbreaker;

class CircuitBreakerConfig
{
    /**
     * @var callable
     */
    private $waitIntervalFunctionInOpenState;
    /**
     * @var callable|null
     */
    private $resultPredicate;
    /**
     * @var callable|null
     */
    private $ignoreExceptionPredicate;
    /**
     * @var callable|null
     */
    private $recordExceptionPredicate;

    public function __construct(
        private readonly float $failureRateThreshold,
        private readonly float $slowCallRateThreshold,
        private readonly int $slowCallDurationThreshold,
        private readonly int $permittedNumberOfCallsInHalfOpenState,
        private readonly int $maxWaitDurationInHalfOpenState,
        private readonly SlideWindowType $slidingWindowType,
        private readonly int $slidingWindowSize,
        private readonly int $minimumNumberOfCalls,
        callable $waitIntervalFunctionInOpenState,
        ?callable $resultPredicate,
        private readonly array $ignoreExceptions,
        ?callable $ignoreExceptionPredicate,
        private readonly array $recordExceptions,
        ?callable $recordExceptionPredicate)
    {
        $this->waitIntervalFunctionInOpenState = $waitIntervalFunctionInOpenState;
        $this->resultPredicate = $resultPredicate;
        $this->ignoreExceptionPredicate = $ignoreExceptionPredicate;
        $this->recordExceptionPredicate = $recordExceptionPredicate;
    }

    /**
     * return true if the result should count as a failure.
     *
     * @param mixed $result
     *
     * @return bool
     */
    public function isFailureResult(mixed $result): bool
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
