<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\event\EventListenerInterface;
use kuiper\swoole\event\ReceiveEvent;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Webmozart\Assert\Assert;

class RoutedTcpReceiveEventListener implements EventListenerInterface, LoggerAwareInterface
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
        Assert::isInstanceOf($event, ReceiveEvent::class);
        /** @var ReceiveEvent $event */
        $port = $event->getServer()->getConnectionInfo($event->getClientId())->getServerPort();
        if (isset($this->routes[$port])) {
            call_user_func($this->routes[$port], $event);
        } else {
            $this->logger->error(static::TAG."Unknown tcp port $port");
        }
    }

    public function getSubscribedEvent(): string
    {
        return ReceiveEvent::class;
    }
}
