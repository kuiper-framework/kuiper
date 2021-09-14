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

    public function mapRequest(callable $callback): self
    {
        $this->request = $callback($this->request);

        return $this;
    }

    /**
     * @return RpcRequestInterface
     */
    public function getRequest(): RpcRequestInterface
    {
        return $this->request;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(): array
    {
        $response = $this->buildMiddlewareStack($this->requestHandler)->handle($this->request);

        return $response->getRequest()->getRpcMethod()->getResult() ?? [];
    }

    public static function create(object $client, string $method, array $args): self
    {
        if (method_exists($client, 'getRpcExecutorFactory')) {
            $rpcExecutorFactory = $client->getRpcExecutorFactory();
        } else {
            $property = new \ReflectionProperty($client, 'rpcExecutorFactory');
            $property->setAccessible(true);
            $rpcExecutorFactory = $property->getValue($client);
        }

        return $rpcExecutorFactory->createExecutor($client, $method, $args);
    }
}
