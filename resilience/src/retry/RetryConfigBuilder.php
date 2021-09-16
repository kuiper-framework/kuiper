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

class RetryConfigBuilder
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
    private $retryExceptions = [];

    /**
     * Configures a list of Throwable classes that are ignored and thus are not retried.
     * This parameter supports subtyping.
     *
     * @var string[]
     */
    private $ignoreExceptions = [];

    /**
     * A boolean to enable or disable throwing of MaxRetriesExceededException when the Retry has reached
     * the configured maxAttempts, and the result is still not passing the retryOnResultPredicate.
     *
     * @var bool
     */
    private $failAfterMaxAttempts = false;

    public function __construct(?RetryConfig $config = null)
    {
        if (null !== $config) {
            $this->maxAttempts = $config->getMaxAttempts();
            $this->waitDuration = $config->getWaitDuration();
            $this->intervalFunction = $config->getIntervalFunction();
            $this->retryOnResult = $config->getRetryOnResult();
            $this->retryOnException = $config->getRetryOnException();
            $this->retryExceptions = $config->getRetryExceptions();
            $this->ignoreExceptions = $config->getIgnoreExceptions();
            $this->failAfterMaxAttempts = $config->isFailAfterMaxAttempts();
        } else {
            $this->maxAttempts = 3;
            $this->waitDuration = 500;
        }
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function setMaxAttempts(int $maxAttempts): RetryConfigBuilder
    {
        $this->maxAttempts = $maxAttempts;

        return $this;
    }

    public function getWaitDuration(): int
    {
        return $this->waitDuration;
    }

    public function setWaitDuration(int $waitDuration): RetryConfigBuilder
    {
        $this->waitDuration = $waitDuration;

        return $this;
    }

    public function getIntervalFunction(): ?callable
    {
        return $this->intervalFunction;
    }

    public function setIntervalFunction(?callable $intervalFunction): RetryConfigBuilder
    {
        $this->intervalFunction = $intervalFunction;

        return $this;
    }

    public function getRetryOnResult(): ?callable
    {
        return $this->retryOnResult;
    }

    public function setRetryOnResult(?callable $retryOnResult): RetryConfigBuilder
    {
        $this->retryOnResult = $retryOnResult;

        return $this;
    }

    public function getRetryOnException(): ?callable
    {
        return $this->retryOnException;
    }

    public function setRetryOnException(?callable $retryOnException): RetryConfigBuilder
    {
        $this->retryOnException = $retryOnException;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getRetryExceptions(): array
    {
        return $this->retryExceptions;
    }

    /**
     * @param string[] $retryExceptions
     */
    public function setRetryExceptions(array $retryExceptions): RetryConfigBuilder
    {
        $this->retryExceptions = $retryExceptions;

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
    public function setIgnoreExceptions(array $ignoreExceptions): RetryConfigBuilder
    {
        $this->ignoreExceptions = $ignoreExceptions;

        return $this;
    }

    public function isFailAfterMaxAttempts(): bool
    {
        return $this->failAfterMaxAttempts;
    }

    public function setFailAfterMaxAttempts(bool $failAfterMaxAttempts): RetryConfigBuilder
    {
        $this->failAfterMaxAttempts = $failAfterMaxAttempts;

        return $this;
    }

    public function build(): RetryConfig
    {
        return new RetryConfig(
            $this->maxAttempts,
            $this->waitDuration,
            $this->intervalFunction,
            $this->retryOnResult,
            $this->retryOnException,
            $this->retryExceptions,
            $this->ignoreExceptions,
            $this->failAfterMaxAttempts
        );
    }
}
