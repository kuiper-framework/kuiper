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

/**
 * @see RetryConfigBuilder
 */
class RetryConfig
{
    /**
     * @var callable|null
     */
    private $intervalFunction;
    /**
     * @var callable|null
     */
    private $retryOnResult;
    /**
     * @var callable|null
     */
    private $retryOnException;

    public function __construct(
        private readonly int $maxAttempts = 3,
        private readonly int $waitDuration = 500,
        ?callable $intervalFunction = null,
        ?callable $retryOnResult = null,
        ?callable $retryOnException = null,
        private readonly array $retryExceptions = [],
        private readonly array $ignoreExceptions = [],
        private readonly bool $failAfterMaxAttempts = false)
    {
        $this->intervalFunction = $intervalFunction;
        $this->retryOnResult = $retryOnResult;
        $this->retryOnException = $retryOnException;
    }

    public function shouldRetryOnException(\Throwable $exception): bool
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

    public function getRetryInterval(int $retryAttempts, ?\Throwable $lastException, mixed $result): int
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
