<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HealthCheckHttpRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly RequestHandlerInterface $delegateRequestHandler,
        private readonly ResponseFactoryInterface $responseFactory)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (in_array($request->getMethod(), ['GET', 'HEAD'], true) && '/' === $request->getUri()->getPath()) {
            $response = $this->responseFactory->createResponse();
            $response->getBody()->write('ok');

            return $response;
        }

        return $this->delegateRequestHandler->handle($request);
    }
}
