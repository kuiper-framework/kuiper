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

namespace kuiper\resilience\circuitbreaker\exception;

use kuiper\resilience\circuitbreaker\CircuitBreaker;
use kuiper\resilience\core\ResilienceException;

class ResultWasFailureException extends \Exception implements ResilienceException
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
