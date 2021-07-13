<?php

declare(strict_types=1);

namespace kuiper\resilience\retry\event;

use kuiper\resilience\retry\Retry;

/**
 * 重试达到最大重试次数，重试失败
 * Class RetryOnError.
 */
class RetryOnError
{
    /**
     * @var Retry
     */
    private $retry;

    /**
     * @var int
     */
    private $numOfAttempts;

    /**
     * @var \Exception
     */
    private $lastException;

    /**
     * RetryOnError constructor.
     */
    public function __construct(Retry $retry, int $numOfAttempts, \Exception $lastException)
    {
        $this->retry = $retry;
        $this->numOfAttempts = $numOfAttempts;
        $this->lastException = $lastException;
    }

    public function getRetry(): Retry
    {
        return $this->retry;
    }

    public function getNumOfAttempts(): int
    {
        return $this->numOfAttempts;
    }

    public function getLastException(): \Exception
    {
        return $this->lastException;
    }
}
