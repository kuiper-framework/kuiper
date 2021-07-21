<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker\exception;

use kuiper\resilience\circuitbreaker\CircuitBreaker;

class ResultWasFailureException extends \Exception
{
    /**
     * @var CircuitBreaker
     */
    private $circuitBreaker;

    /**
     * @var mixed
     */
    private $result;

    /**
     * ResultWasFailureException constructor.
     *
     * @param mixed $result
     */
    public function __construct(CircuitBreaker $circuitBreaker, $result)
    {
        $this->circuitBreaker = $circuitBreaker;
        $this->result = $result;
        parent::__construct('result is failure');
    }

    public function getCircuitBreaker(): CircuitBreaker
    {
        return $this->circuitBreaker;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }
}
