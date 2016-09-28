<?php
namespace chaozhuo\web;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ControllerInterface
{
    /**
     * @param ServerRequestInterface $request
     */
    public function setRequest(ServerRequestInterface $request);

    /**
     * @param ResponseInterface $response
     */
    public function setResponse(ResponseInterface $response);

    /**
     * @return ResponseInterface
     */
    public function getResponse();

    /**
     * Init
     */
    public function initialize();
}
