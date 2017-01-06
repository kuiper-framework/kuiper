<?php
namespace kuiper\web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ControllerInterface
{
    /**
     * @param ServerRequestInterface $request
     */
    public function setRequest(ServerRequestInterface $request);

    /**
     * @return ServerRequestInterface
     */
    public function getRequest();

    /**
     * @param ResponseInterface $response
     */
    public function setResponse(ResponseInterface $response);

    /**
     * @return ResponseInterface
     */
    public function getResponse();

    /**
     * Initialize controller
     */
    public function initialize();
}
