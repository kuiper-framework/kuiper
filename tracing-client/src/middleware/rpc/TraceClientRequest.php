<?php

declare(strict_types=1);

namespace kuiper\tracing\middleware\rpc;

use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\tracing\middleware\AbstractClientMiddleware;

use const OpenTracing\Formats\HTTP_HEADERS;

use Psr\Http\Message\RequestInterface;

class TraceClientRequest extends AbstractClientMiddleware implements MiddlewareInterface
{
    public function __construct()
    {
        $this->format = HTTP_HEADERS;
    }

    protected function getMethodName(RequestInterface $request): string
    {
        /** @var RpcRequestInterface $request */
        $methodName = $request->getRpcMethod()->getMethodName();
        $serviceName = $request->getRpcMethod()->getServiceLocator()->getName();

        return $serviceName.'.'.$methodName;
    }

    protected function getParamsData(RequestInterface $request): string
    {
        return json_encode($request->getRpcMethod()->getArguments(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $handler): RpcResponseInterface
    {
        return $this->handle($request, function (RpcRequestInterface $request) use ($handler) {
            return $handler->handle($request);
        });
    }
}
