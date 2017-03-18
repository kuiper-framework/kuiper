<?php

namespace kuiper\web\exception;

use kuiper\web\RequestAwareInterface;
use kuiper\web\RequestAwareTrait;
use kuiper\web\ResponseAwareInterface;
use kuiper\web\ResponseAwareTrait;
use LogicException;

/**
 * Http Exception.
 */
abstract class HttpException extends LogicException implements RequestAwareInterface, ResponseAwareInterface
{
    use RequestAwareTrait;
    use ResponseAwareTrait;

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * Gets status code.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Sets status code.
     *
     * @param int
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }
}
