<?php

declare(strict_types=1);

namespace kuiper\swoole\server;

use kuiper\swoole\constants\Event;
use kuiper\swoole\constants\HttpHeaderName;
use kuiper\swoole\constants\HttpServerSetting;
use kuiper\swoole\event\AbstractServerEvent;
use kuiper\swoole\event\RequestEvent;
use kuiper\swoole\exception\BadHttpRequestException;
use kuiper\swoole\http\RequestParser;
use kuiper\swoole\http\ResponseBuilder;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class HttpServer extends SelectTcpServer
{
    public const SERVER_NAME = 'KuiperHttpServer';

    private const TAG = '['.__CLASS__.'] ';

    /**
     * @var HttpMessageFactoryHolder
     */
    private $httpMessageFactoryHolder;

    /**
     * @var ResponseBuilder
     */
    private $responseBuilder;

    /**
     * @var RequestParser[]
     */
    private $requestParsers;

    public function getHttpMessageFactoryHolder(): HttpMessageFactoryHolder
    {
        return $this->httpMessageFactoryHolder;
    }

    public function setHttpMessageFactoryHolder(HttpMessageFactoryHolder $httpMessageFactoryHolder): void
    {
        $this->httpMessageFactoryHolder = $httpMessageFactoryHolder;
    }

    public function getServerRequestFactory(): ServerRequestFactoryInterface
    {
        return $this->httpMessageFactoryHolder->getServerRequestFactory();
    }

    public function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->httpMessageFactoryHolder->getResponseFactory();
    }

    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->httpMessageFactoryHolder->getStreamFactory();
    }

    public function getUploadFileFactory(): UploadedFileFactoryInterface
    {
        return $this->httpMessageFactoryHolder->getUploadFileFactory();
    }

    public function getUriFactory(): UriFactoryInterface
    {
        return $this->httpMessageFactoryHolder->getUriFactory();
    }

    public function getResponseBuilder(): ResponseBuilder
    {
        if (!$this->responseBuilder) {
            $this->responseBuilder = new ResponseBuilder($this->getSettings(), $this->logger);
        }

        return $this->responseBuilder;
    }

    public function setResponseBuilder(ResponseBuilder $responseBuilder): void
    {
        $this->responseBuilder = $responseBuilder;
    }

    protected function dispatch(string $eventName, array $args): ?AbstractServerEvent
    {
        if (in_array($eventName, [Event::CONNECT, Event::CLOSE], true)) {
            return null;
        }
        if (Event::RECEIVE === $eventName) {
            $this->onReceive(...$args);

            return null;
        }

        return parent::dispatch($eventName, $args);
    }

    private function onReceive(int $clientId, int $reactorId, string $data): void
    {
        if (!isset($this->requstParsers[$clientId])) {
            $this->requestParsers[$clientId] = new RequestParser($this, $clientId);
        }
        $requestParser = $this->requestParsers[$clientId];
        try {
            $requestParser->receive($data);
        } catch (BadHttpRequestException $e) {
            $this->sendResponse($clientId, $this->getServerRequestFactory()->createServerRequest('GET', '/'),
                $this->getResponseFactory()->createResponse(400)
                    ->withBody($this->getStreamFactory()->createStream($e->getMessage())));

            return;
        }
        if ($requestParser->isCompleted()) {
            /** @var RequestEvent $event */
            $event = $this->dispatch(Event::REQUEST, [$requestParser->getRequest()]);
            $this->sendResponse($clientId, $event->getRequest(), $event->getResponse()
                ?? $this->getResponseFactory()->createResponse(500));
        }
    }

    private function sendResponse(int $clientId, ServerRequestInterface $request, ResponseInterface $response): void
    {
        $this->send($clientId, $this->getResponseBuilder()->build($request, $response));

        if (!$this->getSettings()->getBool(HttpServerSetting::KEEPALIVE) || 0 === strcasecmp($response->getHeaderLine(HttpHeaderName::CONNECTION), 'close')) {
            $this->close($clientId);
        }
        //清空request缓存区
        unset($this->requestParsers[$clientId]);
    }
}
