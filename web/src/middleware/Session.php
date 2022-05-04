<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\web\middleware;

use kuiper\web\security\SecurityContext;
use kuiper\web\session\SessionFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Session implements MiddlewareInterface
{
    public function __construct(private readonly SessionFactoryInterface $sessionFactory)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $this->sessionFactory->create($request);
        $response = $handler->handle($request->withAttribute(SecurityContext::SESSION, $session));

        return $session->setCookie($response);
    }
}
