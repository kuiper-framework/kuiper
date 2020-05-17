<?php

declare(strict_types=1);

namespace kuiper\web\session;

use kuiper\web\security\SecurityContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddleware implements MiddlewareInterface
{
    /**
     * @var SessionFactoryInterface
     */
    private $sessionFactory;

    public function __construct(SessionFactoryInterface $sessionFactory)
    {
        $this->sessionFactory = $sessionFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $this->sessionFactory->create($request);
        $response = $handler->handle($request->withAttribute(SecurityContext::SESSION, $session));

        return $session->setCookie($response);
    }
}
