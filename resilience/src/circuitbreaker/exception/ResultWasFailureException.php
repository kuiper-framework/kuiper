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
    public function __construct(
        private readonly CircuitBreaker $circuitBreaker,
        private readonly mixed $result)
    {
        parent::__construct('result is failure');
    }

    public function getCircuitBreaker(): CircuitBreaker
    {
        return $this->circuitBreaker;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }
}
