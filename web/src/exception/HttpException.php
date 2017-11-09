<?php

namespace kuiper\web\exception;

use kuiper\web\ResponseAwareInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Http Exception.
 */
abstract class HttpException extends WebException implements ResponseAwareInterface
{
    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var ResponseInterface
     */
    protected $response;

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
     * @param int $statusCode
     *
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response->withStatus($this->getStatusCode());

        return $this;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}
