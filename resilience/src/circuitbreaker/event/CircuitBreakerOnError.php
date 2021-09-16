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

namespace kuiper\resilience\circuitbreaker\event;

use kuiper\resilience\circuitbreaker\CircuitBreaker;

/**
 * 重试结果为成功
 * Class RetryOnSuccess.
 */
class CircuitBreakerOnError
{
    /**
     * @var CircuitBreaker
     */
    private $circuitBreaker;

    /**
     * @var int
     */
    private $duration;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * CircuitBreakerOnError constructor.
     */
    public function __construct(CircuitBreaker $circuitBreaker, int $duration, \Exception $exception)
    {
        $this->circuitBreaker = $circuitBreaker;
        $this->duration = $duration;
        $this->exception = $exception;
    }

    public function getCircuitBreaker(): CircuitBreaker
    {
        return $this->circuitBreaker;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getException(): \Exception
    {
        return $this->exception;
    }
}
