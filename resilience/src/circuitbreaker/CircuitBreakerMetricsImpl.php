<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

use kuiper\resilience\core\Counter;
use kuiper\resilience\core\Metrics;
use kuiper\resilience\core\Outcome;
use kuiper\resilience\core\Snapshot;

class CircuitBreakerMetricsImpl implements CircuitBreakerMetrics
{
    private const RATE_NA = -1;

    /**
     * @var Counter
     */
    private $numberOfNotPermittedCalls;

    /**
     * @var Metrics
     */
    private $metrics;

    /**
     * @var CircuitBreakerConfig
     */
    private $config;

    /**
     * @var Snapshot
     */
    private $snapshot;

    /**
     * CircuitBreakerMetricsImpl constructor.
     */
    public function __construct(Counter $numberOfNotPermittedCalls, Metrics $metrics, CircuitBreakerConfig $config)
    {
        $this->numberOfNotPermittedCalls = $numberOfNotPermittedCalls;
        $this->metrics = $metrics;
        $this->config = $config;
        $this->snapshot = Snapshot::dummy();
    }

    public function onCallNotPermitted(): void
    {
        $this->numberOfNotPermittedCalls->increment();
    }

    /**
     * Records a successful call and checks if the thresholds are exceeded.
     *
     * @return Result the result of the check
     */
    public function onSuccess(int $duration): Result
    {
        if ($duration > $this->config->getSlowCallDurationThreshold()) {
            $this->snapshot = $this->metrics->record($duration, Outcome::SLOW_SUCCESS());
        } else {
            $this->snapshot = $this->metrics->record($duration, Outcome::SUCCESS());
        }

        return $this->checkIfThresholdsExceeded();
    }

    /**
     * Records a failed call and checks if the thresholds are exceeded.
     *
     * @return Result the result of the check
     */
    public function onError(int $duration): Result
    {
        if ($duration > $this->config->getSlowCallDurationThreshold()) {
            $this->snapshot = $this->metrics->record($duration, Outcome::SLOW_ERROR());
        } else {
            $this->snapshot = $this->metrics->record($duration, Outcome::ERROR());
        }

        return $this->checkIfThresholdsExceeded();
    }

    private function checkIfThresholdsExceeded(): Result
    {
        $failureRateInPercentage = $this->getFailureRate();
        $slowCallsInPercentage = $this->getSlowCallRate();

        if (self::RATE_NA == $failureRateInPercentage || self::RATE_NA == $slowCallsInPercentage) {
            return Result::BELOW_MINIMUM_CALLS_THRESHOLD();
        }
        if ($failureRateInPercentage >= $this->config->getFailureRateThreshold()
            && $slowCallsInPercentage >= $this->config->getSlowCallRateThreshold()) {
            return Result::ABOVE_THRESHOLDS();
        }
        if ($failureRateInPercentage >= $this->config->getFailureRateThreshold()) {
            return Result::FAILURE_RATE_ABOVE_THRESHOLDS();
        }

        if ($slowCallsInPercentage >= $this->config->getSlowCallRateThreshold()) {
            return Result::SLOW_CALL_RATE_ABOVE_THRESHOLDS();
        }

        return Result::BELOW_THRESHOLDS();
    }

    /**
     * {@inheritDoc}
     */
    public function getFailureRate(): float
    {
        $calls = $this->snapshot->getNumberOfCalls();
        if (0 === $calls || $calls < $this->config->getMinimumNumberOfCalls()) {
            return self::RATE_NA;
        }

        return $this->snapshot->getNumberOfFailedCalls() * 100 / $calls;
    }

    /**
     * {@inheritDoc}
     */
    public function getSlowCallRate(): float
    {
        $calls = $this->snapshot->getNumberOfCalls();
        if (0 === $calls || $calls < $this->config->getMinimumNumberOfCalls()) {
            return self::RATE_NA;
        }

        return $this->snapshot->getNumberOfSlowCalls() * 100 / $calls;
    }

    /**
     * {@inheritDoc}
     */
    public function getNumberOfSlowCalls(): int
    {
        return $this->snapshot->getNumberOfSlowCalls();
    }

    /**
     * {@inheritDoc}
     */
    public function getNumberOfSlowSuccessfulCalls(): int
    {
        return $this->snapshot->getNumberOfSlowSuccessfulCalls();
    }

    /**
     * {@inheritDoc}
     */
    public function getNumberOfSlowFailedCalls(): int
    {
        return $this->snapshot->getNumberOfSlowFailedCalls();
    }

    /**
     * {@inheritDoc}
     */
    public function getNumberOfBufferedCalls(): int
    {
        return $this->snapshot->getNumberOfCalls();
    }

    /**
     * {@inheritDoc}
     */
    public function getNumberOfFailedCalls(): int
    {
        return $this->snapshot->getNumberOfFailedCalls();
    }

    /**
     * {@inheritDoc}
     */
    public function getNumberOfSuccessfulCalls(): int
    {
        return $this->snapshot->getNumberOfSuccessfulCalls();
    }

    /**
     * {@inheritDoc}
     */
    public function getNumberOfNotPermittedCalls(): int
    {
        return $this->numberOfNotPermittedCalls->get();
    }
}
