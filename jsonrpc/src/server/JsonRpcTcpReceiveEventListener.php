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

use kuiper\event\EventListenerInterface;
use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\jsonrpc\exception\JsonRpcRequestException;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcServerRequestInterface;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\rpc\server\ServerRequestHolder;
use kuiper\swoole\event\ReceiveEvent;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Webmozart\Assert\Assert;

class JsonRpcTcpReceiveEventListener implements EventListenerInterface
{
    public function __construct(
        private readonly ServerRequestFactoryInterface $httpRequestFactory,
        private readonly RpcServerRequestFactoryInterface $serverRequestFactory,
        private readonly RpcRequestHandlerInterface $requestHandler,
        private readonly InvalidRequestHandlerInterface $invalidRequestHandler)
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
        $sender = static function (ResponseInterface $response) use ($server, $event) {
            $server->send($event->getClientId(), (string) $response->getBody());
        };

        $connectionInfo = $server->getConnectionInfo($event->getClientId());
        Assert::notNull($connectionInfo, 'cannot get connection info');
        $uri = sprintf('//%s:%d', 'localhost', $connectionInfo->getServerPort());
        $request = $this->httpRequestFactory->createServerRequest('POST', $uri, $connectionInfo->toArray());
        $request->getBody()->write($event->getData());
        try {
            /** @var JsonRpcRequestInterface|RpcServerRequestInterface $serverRequest */
            $serverRequest = $this->serverRequestFactory->createRequest($request);
        } catch (JsonRpcRequestException $e) {
            $sender($this->invalidRequestHandler->handleInvalidRequest($request, $e));

            return;
        }
        ServerRequestHolder::setRequest($serverRequest);
        $sender($this->requestHandler->handle($serverRequest));
    }

    public function getSubscribedEvent(): string
    {
        return ReceiveEvent::class;
    }
}
