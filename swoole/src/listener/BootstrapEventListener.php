<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\di\ComponentCollection;
use kuiper\swoole\annotation\EventListener;
use kuiper\swoole\event\BootstrapEvent;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BootstrapEventListener implements EventListenerInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * BootstrapEventListener constructor.
     */
    public function __construct(ContainerInterface $container, EventDispatcherInterface $eventDispatcher)
    {
        $this->container = $container;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($event): void
    {
        /** @var EventListener $annotation */
        foreach (ComponentCollection::getComponents(EventListener::class) as $annotation) {
            $eventListener = $this->container->get($annotation->getTarget()->getName());
            $event = $annotation->value;
            if (!$event) {
                if (!$eventListener instanceof EventListenerInterface) {
                    throw new \InvalidArgumentException(sprintf('EventListener %s should implements %s', get_class($eventListener), EventListenerInterface::class));
                }
                $event = $eventListener->getSubscribedEvent();
            }
            $this->eventDispatcher->addListener($event, $eventListener);
        }
    }

    public function getSubscribedEvent(): string
    {
        return BootstrapEvent::class;
    }
}
