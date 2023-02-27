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

namespace kuiper\resilience\retry\event;

use kuiper\resilience\retry\Retry;
use Throwable;

/**
 * 调用抛出的异常非重试类型
 * Class RetryOnIgnoredError.
 */
class RetryOnIgnoredError
{
    /**
     * RetryOnIgnoredError constructor.
     */
    public function __construct(
        private readonly Retry $retry,
        private readonly Throwable $exception)
    {
    }

    public function getRetry(): Retry
    {
        return $this->retry;
    }

    public function getException(): Throwable
    {
        return $this->exception;
    }
}
