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

namespace kuiper\rpc\client;

use kuiper\rpc\MiddlewareSupport;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use ReflectionProperty;

class RpcExecutor implements RpcExecutorInterface
{
    use MiddlewareSupport;

    public function __construct(
        private readonly RpcRequestHandlerInterface $requestHandler,
        private readonly RpcRequestInterface $request,
        array $middlewares)
    {
        $this->middlewares = $middlewares;
    }

    public function mapRequest(callable $callback): self
    {
        return new self($this->requestHandler, $callback($this->request), $this->middlewares);
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
        RpcRequestHolder::push($this->request);
        try {
            $response = $this->buildMiddlewareStack($this->requestHandler)->handle($this->request);

            return $response->getRequest()->getRpcMethod()->getResult();
        } finally {
            RpcRequestHolder::pop();
        }
    }

    /**
     * @throws \ReflectionException
     */
    public static function create(object $client, string $method, array $args): self
    {
        if (method_exists($client, 'getRpcExecutorFactory')) {
            $rpcExecutorFactory = $client->getRpcExecutorFactory();
        } else {
            $property = new ReflectionProperty($client, 'rpcExecutorFactory');
            $rpcExecutorFactory = $property->getValue($client);
        }

        return $rpcExecutorFactory->createExecutor($client, $method, $args);
    }
}
