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

namespace kuiper\jsonrpc\server;

use Exception;
use kuiper\event\EventListenerInterface;
use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\jsonrpc\exception\JsonRpcRequestException;
use kuiper\rpc\ErrorHandlerInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestHelper;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\rpc\server\ServerRequestHolder;
use kuiper\swoole\event\ReceiveEvent;
use Psr\Http\Message\RequestFactoryInterface;
use Webmozart\Assert\Assert;

class JsonRpcTcpReceiveEventListener implements EventListenerInterface
{
    public function __construct(
        private readonly RequestFactoryInterface $httpRequestFactory,
        private readonly RpcServerRequestFactoryInterface $serverRequestFactory,
        private readonly RpcRequestHandlerInterface $requestHandler,
        private readonly InvalidRequestHandlerInterface $invalidRequestHandler,
        private readonly ErrorHandlerInterface $errorHandler)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(object $event): void
    {
        Assert::isInstanceOf($event, ReceiveEvent::class);
        /** @var ReceiveEvent $event */
        $server = $event->getServer();

        $connectionInfo = $server->getConnectionInfo($event->getClientId());
        Assert::notNull($connectionInfo, 'cannot get connection info');
        $request = $this->httpRequestFactory->createRequest('POST', sprintf('//%s:%d', 'localhost', $connectionInfo->getServerPort()));
        $request->getBody()->write($event->getData());
        try {
            /** @var JsonRpcRequestInterface $serverRequest */
            $serverRequest = $this->serverRequestFactory->createRequest($request);
        } catch (JsonRpcRequestException $e) {
            $server->send($event->getClientId(), (string) $this->invalidRequestHandler->handleInvalidRequest($request, $e)
                ->getBody());

            return;
        }
        try {
            $serverRequest = RpcRequestHelper::addConnectionInfo($serverRequest, $connectionInfo);
            ServerRequestHolder::setRequest($serverRequest);
            $response = $this->requestHandler->handle($serverRequest);
            $server->send($event->getClientId(), (string) $response->getBody());
        } catch (Exception $e) {
            /** @var JsonRpcRequestInterface $serverRequest */
            $server->send($event->getClientId(), (string) $this->errorHandler->handle($serverRequest, $e)
                ->getBody());
        }
    }

    public function getSubscribedEvent(): string
    {
        return ReceiveEvent::class;
    }
}
