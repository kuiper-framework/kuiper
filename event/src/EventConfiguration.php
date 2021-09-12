<?php

declare(strict_types=1);

namespace kuiper\event;

use function DI\autowire;
use function DI\get;
use kuiper\di\AwareInjection;
use kuiper\di\Bootstrap;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\helper\PropertyResolverInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventConfiguration implements DefinitionConfiguration, Bootstrap
{
    use ContainerBuilderAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    public function getDefinitions(): array
    {
        $this->containerBuilder->addAwareInjection(AwareInjection::create(EventDispatcherAwareInterface::class));

        return [
            PsrEventDispatcher::class => get(EventDispatcherInterface::class),
            EventDispatcherInterface::class => autowire(EventDispatcher::class),
        ];
    }

    public function boot(ContainerInterface $container): void
    {
        $logger = $container->get(LoggerInterface::class);
        $dispatcher = $container->get(EventDispatcherInterface::class);
        $config = $container->get(PropertyResolverInterface::class);
        $events = [];
        $addListener = static function ($eventName, $listener) use ($container, $dispatcher, $logger, &$events): void {
            $eventListener = is_string($listener) ? $container->get($listener) : $listener;
            if ($eventListener instanceof EventListenerInterface) {
                $event = $eventListener->getSubscribedEvent();
                $dispatcher->addListener($event, $eventListener);
                $events[$event] = true;
                $logger->info(static::TAG."add event listener {$listener} for {$event}");
            } elseif ($eventListener instanceof EventSubscriberInterface) {
                foreach ($eventListener->getSubscribedEvents() as $event) {
                    $dispatcher->addListener($event, $eventListener);
                    $events[$event] = true;
                    $logger->info(static::TAG."add event listener {$listener} for {$event}");
                }
            } elseif (is_string($eventName)) {
                $dispatcher->addListener($eventName, $eventListener);
                $events[$eventName] = true;
            }
        };
        foreach ($config->get('application.listeners', []) as $key => $listener) {
            $addListener($key, $listener);
        }
    }
}
