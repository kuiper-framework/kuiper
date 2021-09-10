<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use kuiper\event\EventListenerInterface;
use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\jsonrpc\exception\JsonRpcRequestException;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestHelper;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\rpc\server\ServerRequestHolder;
use kuiper\swoole\event\ReceiveEvent;
use Psr\Http\Message\RequestFactoryInterface;
use Webmozart\Assert\Assert;

class JsonRpcTcpReceiveEventListener implements EventListenerInterface
{
    /**
     * @var RequestFactoryInterface
     */
    private $httpRequestFactory;

    /**
     * @var RpcServerRequestFactoryInterface
     */
    private $serverRequestFactory;

    /**
     * @var RpcRequestHandlerInterface
     */
    private $requestHandler;

    /**
     * @var ErrorResponseHandlerInterface
     */
    private $errorResponseHandler;

    /**
     * TcpReceiveEventListener constructor.
     */
    public function __construct(
        RequestFactoryInterface $httpRequestFactory,
        RpcServerRequestFactoryInterface $serverRequestFactory,
        RpcRequestHandlerInterface $requestHandler,
        ErrorResponseHandlerInterface $errorResponseHandler)
    {
        $this->httpRequestFactory = $httpRequestFactory;
        $this->serverRequestFactory = $serverRequestFactory;
        $this->requestHandler = $requestHandler;
        $this->errorResponseHandler = $errorResponseHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($event): void
    {
        Assert::isInstanceOf($event, ReceiveEvent::class);
        /** @var ReceiveEvent $event */
        $server = $event->getServer();

        $connectionInfo = $server->getConnectionInfo($event->getClientId());
        Assert::notNull($connectionInfo, 'cannot get connection info');
        $request = $this->httpRequestFactory->createRequest('POST', sprintf('tcp://%s:%d', 'localhost', $connectionInfo->getServerPort()));
        $request->getBody()->write($event->getData());
        try {
            /** @var JsonRpcRequestInterface $serverRequest */
            $serverRequest = $this->serverRequestFactory->createRequest($request);
        } catch (JsonRpcRequestException $e) {
            $server->send($event->getClientId(), $this->errorResponseHandler->handle($e));

            return;
        }
        try {
            $serverRequest = RpcRequestHelper::addConnectionInfo($serverRequest, $connectionInfo);
            ServerRequestHolder::setRequest($serverRequest);
            $response = $this->requestHandler->handle($serverRequest);
            $server->send($event->getClientId(), (string) $response->getBody());
        } catch (\Exception $e) {
            $server->send($event->getClientId(), $this->errorResponseHandler->handle($e, $serverRequest));
        }
    }

    public function getSubscribedEvent(): string
    {
        return ReceiveEvent::class;
    }
}
