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

class RetryConfig
{
    /**
     * The maximum number of attempts (including the initial call as the first attempt).
     *
     * @var int
     */
    private $maxAttempts;

    /**
     * A fixed wait duration between retry attempts.
     *
     * @var int
     */
    private $waitDuration;

    /**
     * A function to modify the waiting interval after a failure. By default the wait duration remains constant.
     *
     * @var callable|null
     */
    private $intervalFunction;

    /**
     * Configures a Predicate which evaluates if a result should be retried.
     * The callable must return true, if the result should be retried, otherwise it must return false.
     *
     * @var callable|null
     */
    private $retryOnResult;

    /**
     * Configures a Predicate which evaluates if an exception should be retried.
     * The callable must return true, if the exception should be retried, otherwise it must return false.
     *
     * @var callable|null
     */
    private $retryOnException;

    /**
     * Configures a list of Throwable classes that are recorded as a failure and thus are retried.
     * This parameter supports subtyping.
     *
     * @var string[]
     */
    private $retryExceptions;

    /**
     * Configures a list of Throwable classes that are ignored and thus are not retried.
     * This parameter supports subtyping.
     *
     * @var string[]
     */
    private $ignoreExceptions;

    /**
     * A boolean to enable or disable throwing of MaxRetriesExceededException when the Retry has reached
     * the configured maxAttempts, and the result is still not passing the retryOnResultPredicate.
     *
     * @var bool
     */
    private $failAfterMaxAttempts;

    public function __construct(
        int $maxAttempts = 3,
        int $waitDuration = 500,
        ?callable $intervalFunction = null,
        ?callable $retryOnResult = null,
        ?callable $retryOnException = null,
        array $retryExceptions = [],
        array $ignoreExceptions = [],
        bool $failAfterMaxAttempts = false)
    {
        $this->maxAttempts = $maxAttempts;
        $this->waitDuration = $waitDuration;
        $this->intervalFunction = $intervalFunction;
        $this->retryOnResult = $retryOnResult;
        $this->retryOnException = $retryOnException;
        $this->retryExceptions = $retryExceptions;
        $this->ignoreExceptions = $ignoreExceptions;
        $this->failAfterMaxAttempts = $failAfterMaxAttempts;
    }

    public function shouldRetryOnException(\Exception $exception): bool
    {
        if (null !== $this->retryOnException) {
            return call_user_func($this->retryOnException, $exception);
        }
        if (!empty($this->retryExceptions)) {
            foreach ($this->retryExceptions as $type) {
                if ($exception instanceof $type) {
                    return true;
                }
            }
        }
        if (!empty($this->ignoreExceptions)) {
            foreach ($this->ignoreExceptions as $type) {
                if ($exception instanceof $type) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param mixed $result
     */
    public function getRetryInterval(int $retryAttempts, ?\Exception $lastException, $result): int
    {
        if (null !== $this->intervalFunction) {
            return call_user_func($this->intervalFunction, $retryAttempts, $this->waitDuration, $lastException, $result);
        }

        return $this->waitDuration;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function getWaitDuration(): int
    {
        return $this->waitDuration;
    }

    public function getIntervalFunction(): ?callable
    {
        return $this->intervalFunction;
    }

    public function getRetryOnResult(): ?callable
    {
        return $this->retryOnResult;
    }

    public function getRetryOnException(): ?callable
    {
        return $this->retryOnException;
    }

    /**
     * @return string[]
     */
    public function getRetryExceptions(): array
    {
        return $this->retryExceptions;
    }

    /**
     * @return string[]
     */
    public function getIgnoreExceptions(): array
    {
        return $this->ignoreExceptions;
    }

    public function isFailAfterMaxAttempts(): bool
    {
        return $this->failAfterMaxAttempts;
    }

    public static function builder(?RetryConfig $config = null): RetryConfigBuilder
    {
        return new RetryConfigBuilder($config);
    }

    public static function ofDefaults(): RetryConfig
    {
        static $default;
        if (null === $default) {
            $default = new RetryConfig();
        }

        return $default;
    }
}
