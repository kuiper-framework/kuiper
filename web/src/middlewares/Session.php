<?php

namespace kuiper\web\middlewares;

use kuiper\web\session\ManagedSessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handle swoole php session.
 */
class Session
{
    /**
     * @var ManagedSessionInterface
     */
    private $session;

    public function __construct(ManagedSessionInterface $session)
    {
        $this->session = $session;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $this->session->setRequest($request);
        $this->session->start();
        $response = $next($request, $response);

        return $this->session->respond($response);
    }
}
