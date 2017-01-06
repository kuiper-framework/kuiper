<?php
namespace kuiper\web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ErrorHandlerInterface
{
    /**
     * @param ServerRequestInterface $request
     * @return self
     */
    public function setRequest(ServerRequestInterface $request);

    /**
     * @param ResponseInterface $response
     * @return self
     */
    public function setResponse(ResponseInterface $response);
    
    /**
     * Handles the exception
     */
    public function handle($e);
}
