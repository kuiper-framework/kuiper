<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\event\EventListenerInterface;
use kuiper\swoole\event\RequestEvent;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class HttpRequestEventListener implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var RequestHandlerInterface
     */
    private $requestHandler;

    public function __construct(RequestHandlerInterface $handler, ?LoggerInterface $logger)
    {
        $this->requestHandler = $handler;
        $this->setLogger($logger ?? new NullLogger());
    }

    /**
     * @param RequestEvent $event
     */
    public function __invoke($event): void
    {
        try {
            $this->logger->debug(static::TAG.'receive request');
            $event->setResponse($this->requestHandler->handle($event->getRequest()));
        } catch (\Exception $e) {
            $this->logger->error(static::TAG.'Uncaught exception: '.get_class($e).': '.$e->getMessage()."\n"
                .$e->getTraceAsString());
        }
    }

    public function getSubscribedEvent(): string
    {
        return RequestEvent::class;
    }
}
