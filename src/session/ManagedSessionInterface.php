<?php
namespace kuiper\web\session;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ManagedSessionInterface extends SessionInterface
{
    /**
     * @param ServerRequestInterface $request
     */
    public function setRequest(ServerRequestInterface $request);

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function respond(ResponseInterface $response);
}
