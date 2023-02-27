<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\event;

use function DI\get;
use function DI\value;

use kuiper\di\AwareInjection;
use kuiper\di\Bootstrap;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\event\attribute\EventListener;
use kuiper\helper\PropertyResolverInterface;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\Application;
use kuiper\swoole\attribute\BootstrapConfiguration;
use kuiper\swoole\server\ServerInterface;
use kuiper\swoole\task\QueueInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcher;
use Psr\Log\LoggerInterface;

#[BootstrapConfiguration]
class EventConfiguration implements DefinitionConfiguration, Bootstrap
{
    use ContainerBuilderAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    public function getDefinitions(): array
    {
        $this->containerBuilder->addAwareInjection(AwareInjection::create(EventDispatcherAwareInterface::class));
        $this->containerBuilder->defer(function (ContainerInterface $container): void {
            $taskWorkers = (int) $container->get('application.swoole.task_worker_num');
            if ($taskWorkers > 0) {
                $eventDispatcher = $container->get(PsrEventDispatcher::class);
                if ($eventDispatcher instanceof AsyncEventDispatcher && $container->has(ServerInterface::class)) {
                    $eventDispatcher->setServer($container->get(ServerInterface::class));
                    $eventDispatcher->setTaskQueue($container->get(QueueInterface::class));
                }
            }
        });
        $eventDispatcher = Application::getInstance()->getEventDispatcher();

        return [
            PsrEventDispatcher::class => value(new AsyncEventDispatcher($eventDispatcher)),
            AsyncEventDispatcherInterface::class => get(PsrEventDispatcher::class),
            EventRegistryInterface::class => value($eventDispatcher),
        ];
    }

    public function boot(ContainerInterface $container): void
    {
        $logger = $container->has(LoggerFactoryInterface::class)
            ? $container->get(LoggerFactoryInterface::class)->create(__CLASS__)
            : $container->get(LoggerInterface::class);
        /** @var EventRegistryInterface $dispatcher */
        $dispatcher = $container->get(EventRegistryInterface::class);
        $config = $container->get(PropertyResolverInterface::class);
        $events = [];
        $addListener = static function (?string $eventName, string|object $listener) use ($container, $logger, $dispatcher, &$events): void {
            $eventListener = is_string($listener) ? $container->get($listener) : $listener;
            if ($eventListener instanceof EventListenerInterface) {
                $event = $eventListener->getSubscribedEvent();
                $dispatcher->addListener($event, $eventListener);
                $events[$event] = true;
                $logger->debug(static::TAG."add event listener {$listener} for {$event}");
            } elseif ($eventListener instanceof EventSubscriberInterface) {
                foreach ($eventListener->getSubscribedEvents() as $event) {
                    $dispatcher->addListener($event, $eventListener);
                    $events[$event] = true;
                    $logger->debug(static::TAG."add event listener {$listener} for {$event}");
                }
            } elseif (is_string($eventName)) {
                $dispatcher->addListener($eventName, $eventListener);
                $events[$eventName] = true;
            }
        };
        foreach (ComponentCollection::getComponents(EventListener::class) as $attribute) {
            /** @var EventListener $attribute */
            $addListener($attribute->getEventName(), $attribute->getComponentId());
        }
        foreach ($config->get('application.bootstrap_listeners', []) as $key => $listener) {
            $addListener(is_string($key) ? $key : null, $listener);
        }
        if (!Application::getInstance()->isBootstrapping()) {
            foreach ($config->get('application.listeners', []) as $key => $listener) {
                $addListener(is_string($key) ? $key : null, $listener);
            }
        }
    }
}
