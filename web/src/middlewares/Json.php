<?php

namespace kuiper\web\middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Json
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        return $next($request, $response->withHeader('Content-Type', 'application/json'));
    }
}
