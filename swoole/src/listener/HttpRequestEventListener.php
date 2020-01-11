<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\swoole\event\RequestEvent;
use kuiper\swoole\http\ResponseSenderInterface;
use kuiper\swoole\http\ServerRequestFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class HttpRequestEventListener implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ServerRequestFactoryInterface
     */
    private $serverRequestFactory;
    /**
     * @var RequestHandlerInterface
     */
    private $requestHandler;
    /**
     * @var ResponseSenderInterface
     */
    private $responseSender;

    public function __construct(ServerRequestFactoryInterface $serverRequestFactory, RequestHandlerInterface $handler, ResponseSenderInterface $responseSender)
    {
        $this->serverRequestFactory = $serverRequestFactory;
        $this->requestHandler = $handler;
        $this->responseSender = $responseSender;
    }

    /**
     * @param RequestEvent $event
     */
    public function __invoke($event): void
    {
        try {
            $this->logger->info('on request');
            $response = $this->requestHandler->handle($this->serverRequestFactory->createServerRequest($event->getRequest()));
            $this->responseSender->send($response, $event->getResponse());
        } catch (\Exception $e) {
            $this->logger->error('Uncaught exception: '.get_class($e).': '.$e->getMessage()."\n".$e->getTraceAsString());
        }
    }

    public function getSubscribedEvent(): string
    {
        return RequestEvent::class;
    }
}
