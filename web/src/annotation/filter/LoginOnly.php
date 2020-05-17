<?php

declare(strict_types=1);

namespace kuiper\web\annotation\filter;

use kuiper\web\security\SecurityContext;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class LoginOnly extends AbstractFilter
{
    /**
     * @var int
     */
    public $priority = 101;

    /**
     * {@inheritdoc}
     */
    public function createMiddleware(ContainerInterface $container): MiddlewareInterface
    {
        return new class() implements MiddlewareInterface {
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
        };
    }
}
