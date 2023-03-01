<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\event\EventListenerInterface;
use kuiper\swoole\event\RequestEvent;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Webmozart\Assert\Assert;

class RoutedHttpRequestEventListener implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @param array<int, EventListenerInterface> $routes
     */
    public function __construct(private readonly array $routes)
    {
    }

    public function __invoke(object $event): void
    {
        Assert::isInstanceOf($event, RequestEvent::class);
        /** @var RequestEvent $event */
        $serverRequest = $event->getRequest();
        $port = $serverRequest->getServerParams()['SERVER_PORT'];
        if (isset($this->routes[$port])) {
            call_user_func($this->routes[$port], $event);
        } else {
            $this->logger->error(static::TAG."Unknown http port $port");
        }
    }

    public function getSubscribedEvent(): string
    {
        return RequestEvent::class;
    }
}
