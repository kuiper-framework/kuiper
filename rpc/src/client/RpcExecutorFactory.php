<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\transporter\TransporterInterface;

class RpcExecutorFactory implements RpcExecutorFactoryInterface
{
    /**
     * @var RpcRequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var RpcRequestHandlerInterface
     */
    private $rpcRequestHandler;

    /**
     * @var array
     */
    private $middlewares;

    /**
     * RpcExecutorFactory constructor.
     *
     * @param RpcRequestFactoryInterface $requestFactory
     * @param TransporterInterface       $transporter
     * @param array                      $middlewares
     */
    public function __construct(RpcRequestFactoryInterface $requestFactory, RpcRequestHandlerInterface $requestHandler, array $middlewares = [])
    {
        $this->rpcRequestHandler = $requestHandler;
        $this->requestFactory = $requestFactory;
        $this->middlewares = $middlewares;
    }

    /**
     * {@inheritDoc}
     */
    public function createExecutor(object $proxy, string $method, array $args): RpcExecutorInterface
    {
        return new RpcExecutor($this->rpcRequestHandler, $this->requestFactory->createRequest($proxy, $method, $args), $this->middlewares);
    }
}
