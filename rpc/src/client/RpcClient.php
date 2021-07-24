<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\MiddlewareSupport;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\rpc\transporter\TransporterInterface;

class RpcClient implements RpcClientInterface, RpcRequestHandlerInterface
{
    use MiddlewareSupport;

    /**
     * @var RpcRequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var TransporterInterface
     */
    private $transporter;

    /**
     * @var RpcResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * AbstractRpcClient constructor.
     *
     * @param MiddlewareInterface[] $middlewares
     */
    public function __construct(TransporterInterface $transporter, RpcRequestFactoryInterface $requestFactory, RpcResponseFactoryInterface $responseFactory, array $middlewares = [])
    {
        $this->requestFactory = $requestFactory;
        $this->middlewares = $middlewares;
        $this->transporter = $transporter;
        $this->responseFactory = $responseFactory;
    }

    public function getRequestFactory(): RpcRequestFactoryInterface
    {
        return $this->requestFactory;
    }

    public function getTransporter(): TransporterInterface
    {
        return $this->transporter;
    }

    /**
     * @return RpcResponseFactoryInterface
     */
    public function getResponseFactory()
    {
        return $this->responseFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(RpcRequestInterface $request): RpcResponseInterface
    {
        return $this->responseFactory->createResponse($request, $this->transporter->send($request));
    }

    /**
     * {@inheritDoc}
     */
    public function createRequest(object $proxy, string $method, array $args): RpcRequestInterface
    {
        return $this->requestFactory->createRequest($proxy, $method, $args);
    }

    /**
     * {@inheritDoc}
     */
    public function sendRequest(RpcRequestInterface $request): array
    {
        $response = $this->buildMiddlewareStack($this)->handle($request);

        return $response->getRequest()->getInvokingMethod()->getResult() ?? [];
    }
}
