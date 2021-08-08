<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

use kuiper\rpc\MiddlewareSupport;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;

class RpcExecutor implements RpcExecutorInterface
{
    use MiddlewareSupport;

    /**
     * @var RpcRequestInterface
     */
    private $request;
    /**
     * @var RpcRequestHandlerInterface
     */
    private $requestHandler;

    public function __construct(RpcRequestHandlerInterface $requestHandler, RpcRequestInterface $request, array $middlewares)
    {
        $this->request = $request;
        $this->requestHandler = $requestHandler;
        $this->middlewares = $middlewares;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(): array
    {
        $response = $this->buildMiddlewareStack($this->requestHandler)->handle($this->request);

        return $response->getRequest()->getRpcMethod()->getResult() ?? [];
    }
}
