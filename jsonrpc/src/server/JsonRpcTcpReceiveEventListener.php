<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use kuiper\event\EventListenerInterface;
use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\jsonrpc\exception\JsonRpcRequestException;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestHelper;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\serializer\normalizer\ExceptionNormalizer;
use kuiper\swoole\event\ReceiveEvent;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Webmozart\Assert\Assert;

class JsonRpcTcpReceiveEventListener implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

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
     * @var ExceptionNormalizer
     */
    private $exceptionNormalizer;

    /**
     * TcpReceiveEventListener constructor.
     */
    public function __construct(
        RequestFactoryInterface $httpRequestFactory,
        RpcServerRequestFactoryInterface $serverRequestFactory,
        RpcRequestHandlerInterface $requestHandler,
        ExceptionNormalizer $exceptionNormalizer)
    {
        $this->httpRequestFactory = $httpRequestFactory;
        $this->serverRequestFactory = $serverRequestFactory;
        $this->requestHandler = $requestHandler;
        $this->exceptionNormalizer = $exceptionNormalizer;
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
            $server->send($event->getClientId(), $this->createRequestErrorResponse($e));

            return;
        }
        try {
            $response = $this->requestHandler->handle(RpcRequestHelper::addConnectionInfo($serverRequest, $connectionInfo));
            $server->send($event->getClientId(), (string) $response->getBody());
        } catch (\Exception $e) {
            $server->send($event->getClientId(), $this->createErrorResponse($serverRequest, $e));
        }
    }

    public function getSubscribedEvent(): string
    {
        return ReceiveEvent::class;
    }

    private function createRequestErrorResponse(JsonRpcRequestException $e): string
    {
        return JsonRpcProtocol::encode([
            'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
            'id' => $e->getRequestId(),
            'error' => [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ],
        ]);
    }

    private function createErrorResponse(JsonRpcRequestInterface $rpcRequest, \Exception $e): string
    {
        return JsonRpcProtocol::encode([
            'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
            'id' => $rpcRequest->getRequestId(),
            'error' => [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'data' => $this->exceptionNormalizer->normalize($e),
            ],
        ]);
    }
}
