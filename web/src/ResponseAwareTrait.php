<?php

namespace kuiper\web;

use Psr\Http\Message\ResponseInterface;

trait ResponseAwareTrait
{
    /**
     * A response object to send to the HTTP client.
     *
     * @var ResponseInterface
     */
    protected $response;

    /**
     * Sets response.
     *
     * @param ResponseInterface $response
     *
     * @return $this
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Gets response.
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}
