<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\MiddlewareSupport;
use kuiper\rpc\RequestHandlerInterface;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\ResponseInterface;
use kuiper\rpc\transporter\TransporterInterface;

class RpcClient implements RpcClientInterface, RequestHandlerInterface
{
    use MiddlewareSupport;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var TransporterInterface
     */
    private $transporter;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * AbstractRpcClient constructor.
     *
     * @param MiddlewareInterface[] $middlewares
     */
    public function __construct(TransporterInterface $transporter, RequestFactoryInterface $requestFactory, ResponseFactoryInterface $responseFactory, array $middlewares = [])
    {
        $this->requestFactory = $requestFactory;
        $this->middlewares = $middlewares;
        $this->transporter = $transporter;
        $this->responseFactory = $responseFactory;
    }

    public function getRequestFactory(): RequestFactoryInterface
    {
        return $this->requestFactory;
    }

    public function getTransporter(): TransporterInterface
    {
        return $this->transporter;
    }

    /**
     * @return ResponseFactoryInterface
     */
    public function getResponseFactory()
    {
        return $this->responseFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(RequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createResponse($request, $this->transporter->send($request));
    }

    /**
     * {@inheritDoc}
     */
    public function createRequest(object $proxy, string $method, array $args): RequestInterface
    {
        return $this->requestFactory->createRequest($proxy, $method, $args);
    }

    /**
     * {@inheritDoc}
     */
    public function sendRequest(RequestInterface $request): array
    {
        $response = $this->buildMiddlewareStack($this)->handle($request);

        return $response->getRequest()->getInvokingMethod()->getResult() ?? [];
    }
}
