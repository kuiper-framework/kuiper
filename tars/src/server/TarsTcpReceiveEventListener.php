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

namespace kuiper\tars\server;

use Exception;
use kuiper\event\EventListenerInterface;
use kuiper\rpc\exception\InvalidRequestException;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestHelper;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\rpc\server\ServerRequestHolder;
use kuiper\swoole\event\ReceiveEvent;
use kuiper\tars\core\TarsRequestInterface;
use kuiper\tars\exception\TarsRequestException;
use kuiper\tars\stream\ResponsePacket;
use Psr\Http\Message\RequestFactoryInterface;
use Webmozart\Assert\Assert;

class TarsTcpReceiveEventListener implements EventListenerInterface
{
    public function __construct(
        private readonly RequestFactoryInterface $httpRequestFactory,
        private readonly RpcServerRequestFactoryInterface $serverRequestFactory,
        private readonly RpcRequestHandlerInterface $requestHandler)
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
            $serverRequest = $this->serverRequestFactory->createRequest($request);
        } catch (TarsRequestException $e) {
            $server->send($event->getClientId(), (string) $this->createInvalidTarsRequestResponse($e)->encode());

            return;
        } catch (InvalidRequestException $e) {
            $server->send($event->getClientId(), (string) $this->createInvalidRequestResponse($e)->encode());

            return;
        }
        try {
            $serverRequest = RpcRequestHelper::addConnectionInfo($serverRequest, $connectionInfo);
            ServerRequestHolder::setRequest($serverRequest);
            $response = $this->requestHandler->handle($serverRequest);
            $server->send($event->getClientId(), (string) $response->getBody());
        } catch (Exception $e) {
            /** @var TarsRequestInterface $serverRequest */
            $server->send($event->getClientId(), (string) $this->createErrorResponse($serverRequest, $e)->encode());
        }
    }

    public function getSubscribedEvent(): string
    {
        return ReceiveEvent::class;
    }

    private function createInvalidTarsRequestResponse(TarsRequestException $e): ResponsePacket
    {
        $packet = new ResponsePacket();

        $requestPacket = $e->getPacket();
        $packet->iRequestId = $requestPacket->iRequestId;
        $packet->iVersion = $requestPacket->iVersion;
        $packet->cPacketType = $requestPacket->cPacketType;
        $packet->iMessageType = $requestPacket->iMessageType;
        $packet->iRet = $e->getCode();
        $packet->sResultDesc = $e->getMessage();
        $packet->sBuffer = '';

        return $packet;
    }

    private function createErrorResponse(TarsRequestInterface $request, Exception $e): ResponsePacket
    {
        $packet = ResponsePacket::createFromRequest($request);
        $packet->iRet = $e->getCode();
        $packet->sResultDesc = $e->getMessage();
        $packet->sBuffer = '';

        return $packet;
    }

    private function createInvalidRequestResponse(InvalidRequestException $e): ResponsePacket
    {
        $packet = new ResponsePacket();
        $packet->iRet = $e->getCode();
        $packet->sResultDesc = $e->getMessage();
        $packet->sBuffer = '';

        return $packet;
    }
}
