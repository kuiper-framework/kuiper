<?php

declare(strict_types=1);

namespace kuiper\rpc\client\middleware;

use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;

class NamespaceAsHost implements MiddlewareInterface
{
    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $handler): RpcResponseInterface
    {
        $uri = $request->getUri();
        if ('' === $uri->getHost()) {
            return $handler->handle($request->withUri(
                $uri->withHost($request->getRpcMethod()->getServiceLocator()->getNamespace())
            ));
        }

        return $handler->handle($request);
    }
}
