<?php

declare(strict_types=1);

namespace kuiper\resilience\retry\event;

use kuiper\resilience\retry\Retry;

/**
 * 调用抛出的异常非重试类型
 * Class RetryOnIgnoredError.
 */
class RetryOnIgnoredError
{
    /**
     * @var Retry
     */
    private $retry;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * RetryOnIgnoredError constructor.
     */
    public function __construct(Retry $retry, \Exception $exception)
    {
        $this->retry = $retry;
        $this->exception = $exception;
    }

    public function getRetry(): Retry
    {
        return $this->retry;
    }

    public function getException(): \Exception
    {
        return $this->exception;
    }
}
