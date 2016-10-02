<?php
namespace kuiper\web\middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use kuiper\web\session\ManagedSessionInterface;


/**
 * Handle swoole php session
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
        if (ini_get('session.auto_start')) {
            $this->session->start();
        }
        $response = $next($request, $response);
        return $this->session->respond($response);
    }
}