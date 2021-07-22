<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\event\EventListenerInterface;
use kuiper\rpc\RequestHandlerInterface;
use kuiper\rpc\server\ServerRequestFactoryInterface;
use kuiper\swoole\event\ReceiveEvent;
use kuiper\tars\core\TarsRequestInterface;
use kuiper\tars\stream\ResponsePacket;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Webmozart\Assert\Assert;

class TcpReceiveEventListener implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var RequestFactoryInterface
     */
    private $httpRequestFactory;

    /**
     * @var ServerRequestFactoryInterface
     */
    private $serverRequestFactory;

    /**
     * @var RequestHandlerInterface
     */
    private $requestHandler;

    /**
     * TcpReceiveEventListener constructor.
     */
    public function __construct(RequestFactoryInterface $httpRequestFactory, ServerRequestFactoryInterface $serverRequestFactory, RequestHandlerInterface $requestHandler)
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
        try {
            $request = $this->httpRequestFactory->createRequest('POST', sprintf('tcp://%s:%d', 'localhost', $connectionInfo->getServerPort()));
            $request->getBody()->write($event->getData());
            /** @var TarsRequestInterface $serverRequest */
            $serverRequest = $this->serverRequestFactory->createRequest($request);
        } catch (\Exception $e) {
            $server->send($event->getClientId(), (string) $this->createRequestErrorResponse($e)->encode());

            return;
        }
        try {
            $response = $this->requestHandler->handle($serverRequest);
            $server->send($event->getClientId(), (string) $response->getBody());
        } catch (\Exception $e) {
            $server->send($event->getClientId(), (string) $this->createErrorResponse($serverRequest, $e)->encode());
        }
    }

    public function getSubscribedEvent(): string
    {
        return ReceiveEvent::class;
    }

    private function createRequestErrorResponse(\Exception $e): ResponsePacket
    {
        $packet = new ResponsePacket();
        $packet->iRet = $e->getCode();
        $packet->sResultDesc = $e->getMessage();

        return $packet;
    }

    private function createErrorResponse(TarsRequestInterface $request, \Exception $e): ResponsePacket
    {
        $packet = ResponsePacket::createFromRequest($request);
        $packet->iRet = $e->getCode();
        $packet->sResultDesc = $e->getMessage();

        return $packet;
    }
}
