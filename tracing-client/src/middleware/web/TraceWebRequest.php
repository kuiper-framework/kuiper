<?php

declare(strict_types=1);

namespace kuiper\tracing\middleware\web;

use kuiper\tracing\Config;
use kuiper\tracing\middleware\AbstractServerMiddleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TraceWebRequest extends AbstractServerMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Config $config)
    {
        parent::__construct($this->config);
    }

    protected function getMethodName(RequestInterface $request): string
    {
        return $request->getMethod().' '.$request->getUri()->getPath();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->handle($request, function (ServerRequestInterface $request) use ($handler) {
            return $handler->handle($request);
        });
    }
}
