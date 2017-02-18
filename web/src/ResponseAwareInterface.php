<?php

namespace kuiper\web;

use Psr\Http\Message\ResponseInterface;

interface ResponseAwareInterface
{
    /**
     * Sets response
     *
     * @param ResponseInterface $response
     */
    public function setResponse(ResponseInterface $response);
}
