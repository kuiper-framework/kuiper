<?php

namespace kuiper\web\exception;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class WrappedException extends WebException
{
    private $request;

    private $response;

    /**
     * WrappedException constructor.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param $prev
     */
    public function __construct($request, $response, $prev)
    {
        $this->request = $request;
        $this->response = $response;

        parent::__construct('', 0, $prev);
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}
