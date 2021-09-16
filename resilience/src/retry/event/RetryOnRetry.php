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

/**
 * 发生重试
 * Class RetryOnRetry.
 */
class RetryOnRetry
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
     * @var int
     */
    private $interval;

    /**
     * @var \Exception|null
     */
    private $lastException;

    /**
     * @var mixed
     */
    private $result;

    /**
     * RetryOnRetry constructor.
     *
     * @param mixed $result
     */
    public function __construct(Retry $retry, int $numOfAttempts, int $interval, ?\Exception $lastException, $result)
    {
        $this->retry = $retry;
        $this->numOfAttempts = $numOfAttempts;
        $this->interval = $interval;
        $this->lastException = $lastException;
        $this->result = $result;
    }

    public function getRetry(): Retry
    {
        return $this->retry;
    }

    public function getNumOfAttempts(): int
    {
        return $this->numOfAttempts;
    }

    public function getInterval(): int
    {
        return $this->interval;
    }

    public function getLastException(): ?\Exception
    {
        return $this->lastException;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }
}
