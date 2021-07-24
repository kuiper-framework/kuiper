<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\di\ComponentCollection;
use kuiper\di\ContainerAwareInterface;
use kuiper\di\ContainerAwareTrait;
use kuiper\event\annotation\EventListener;
use kuiper\event\EventListenerInterface;
use kuiper\event\EventSubscriberInterface;
use kuiper\swoole\Application;
use kuiper\swoole\constants\ProcessType;
use kuiper\swoole\event\RequestEvent;
use kuiper\swoole\event\WorkerStartEvent;
use kuiper\swoole\ServerConfig;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

class WorkerStartEventListener implements EventListenerInterface, LoggerAwareInterface, ContainerAwareInterface
{
    use LoggerAwareTrait;
    use ContainerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * WorkerStartEventListener constructor.
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, ?LoggerInterface $logger)
    {
        $this->setLogger($logger ?? new NullLogger());
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($event): void
    {
        Assert::isInstanceOf($event, WorkerStartEvent::class);
        /* @var WorkerStartEvent $event */
        $this->changeProcessTitle($event);
        $this->seedRandom();
        $this->addEventListeners($event);
    }

    public function getSubscribedEvent(): string
    {
        return WorkerStartEvent::class;
    }

    /**
     * @param WorkerStartEvent $event
     */
    private function changeProcessTitle($event): void
    {
        $serverName = $event->getServer()->getServerConfig()->getServerName();
        $title = sprintf('%s: %s%s %d process', $serverName,
            ($event->getServer()->isTaskWorker() ? 'task ' : ''), ProcessType::WORKER, $event->getWorkerId());
        @cli_set_process_title($title);
        $this->logger->debug(static::TAG."start worker {$title}");
    }

    /**
     * @see https://wiki.swoole.com/#/getting_started/notice?id=mt_rand%e9%9a%8f%e6%9c%ba%e6%95%b0
     */
    private function seedRandom(): void
    {
        mt_srand();
    }

    private function addEventListeners(WorkerStartEvent $event): array
    {
        $config = Application::getInstance()->getConfig();
        $events = [];
        foreach ($config->get('application.listeners', []) as $eventName => $listenerId) {
            $events[] = $this->attach($event, $listenerId, is_string($eventName) ? $eventName : null);
        }
        /** @var EventListener $annotation */
        foreach (ComponentCollection::getAnnotations(EventListener::class) as $annotation) {
            $listener = $this->container->get($annotation->getComponentId());
            if ($listener instanceof EventListenerInterface) {
                $events[] = $this->attach($event, $annotation->getComponentId(), $annotation->value);
            } elseif ($listener instanceof EventSubscriberInterface) {
                foreach ($listener->getSubscribedEvents() as $eventName) {
                    $events[] = $this->attach($event, $annotation->getComponentId(), $eventName);
                }
            } else {
                throw new \InvalidArgumentException($annotation->getTarget()->getName().' should implements '.EventListenerInterface::class);
            }
        }
        $serverConfig = $this->container->get(ServerConfig::class);
        if ($serverConfig->getPort()->isHttpProtocol() && !in_array(RequestEvent::class, $events, true)) {
            $this->attach($event, HttpRequestEventListener::class);
        }

        return $events;
    }

    /**
     * @param string $listenerId
     * @param string $eventName
     *
     * @return string
     */
    private function attach(WorkerStartEvent $event, string $listenerId, ?string $eventName = null): string
    {
        $this->logger->debug(static::TAG."attach $listenerId");
        $listener = $this->container->get($listenerId);

        if ($listener instanceof EventListenerInterface) {
            $eventName = $listener->getSubscribedEvent();
        }
        if (WorkerStartEvent::class === $eventName) {
            $listener($event);

            return $eventName;
        }
        if (is_string($eventName)) {
            $this->eventDispatcher->addListener($eventName, $listener);

            return $eventName;
        }

        throw new \InvalidArgumentException("EventListener $listenerId does not bind to any event");
    }
}
