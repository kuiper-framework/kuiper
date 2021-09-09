<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\event\EventListenerInterface;
use kuiper\rpc\exception\InvalidRequestException;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestHelper;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\swoole\event\ReceiveEvent;
use kuiper\tars\core\TarsRequestInterface;
use kuiper\tars\exception\TarsRequestException;
use kuiper\tars\stream\ResponsePacket;
use Psr\Http\Message\RequestFactoryInterface;
use Webmozart\Assert\Assert;

class TarsTcpReceiveEventListener implements EventListenerInterface
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
     * TcpReceiveEventListener constructor.
     */
    public function __construct(RequestFactoryInterface $httpRequestFactory, RpcServerRequestFactoryInterface $serverRequestFactory, RpcRequestHandlerInterface $requestHandler)
    {
        $this->httpRequestFactory = $httpRequestFactory;
        $this->serverRequestFactory = $serverRequestFactory;
        $this->requestHandler = $requestHandler;
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
            /** @var TarsRequestInterface $serverRequest */
            $serverRequest = $this->serverRequestFactory->createRequest($request);
        } catch (TarsRequestException $e) {
            $server->send($event->getClientId(), (string) $this->createInvalidTarsRequestResponse($e)->encode());

            return;
        } catch (InvalidRequestException $e) {
            $server->send($event->getClientId(), (string) $this->createInvalidRequestResponse($e)->encode());

            return;
        }
        try {
            $response = $this->requestHandler->handle(RpcRequestHelper::addConnectionInfo($serverRequest, $connectionInfo));
            $server->send($event->getClientId(), (string) $response->getBody());
        } catch (\Exception $e) {
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

    private function createErrorResponse(TarsRequestInterface $request, \Exception $e): ResponsePacket
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
