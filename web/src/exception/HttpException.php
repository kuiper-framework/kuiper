<?php
namespace kuiper\web\exception;

use kuiper\web\RequestAwareInterface;
use kuiper\web\RequestAwareTrait;
use kuiper\web\ResponseAwareInterface;
use kuiper\web\ResponseAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Http Exception
 */
class HttpException extends RuntimeException implements RequestAwareInterface, ResponseAwareInterface
{
    use RequestAwareTrait;
    use ResponseAwareTrait {
        getResponse as protected getPsrResponse;
    }

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * Gets status code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Sets status code
     *
     * @param int
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function getResponse()
    {
        return $this->getPsrResponse()->withStatus($this->statusCode);
    }
}
