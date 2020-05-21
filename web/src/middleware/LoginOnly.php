<?php

declare(strict_types=1);

namespace kuiper\web\middleware;

use kuiper\web\security\SecurityContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;

class LoginOnly implements MiddlewareInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (SecurityContext::fromRequest($request)->getAuth()->isGuest()) {
            throw new HttpUnauthorizedException($request);
        }

        return $handler->handle($request);
    }
}
