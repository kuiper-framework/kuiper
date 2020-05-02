<?php

declare(strict_types=1);

namespace kuiper\swoole;

use function DI\autowire;
use function DI\factory;
use kuiper\di\AwareInjection;
use kuiper\di\ContainerBuilder;
use kuiper\swoole\constants\ServerSetting;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\event\ManagerStartEvent;
use kuiper\swoole\event\StartEvent;
use kuiper\swoole\listener\ManagerStartEventListener;
use kuiper\swoole\listener\StartEventListener;
use kuiper\swoole\server\ServerInterface;
use kuiper\swoole\task\Queue;
use kuiper\swoole\task\QueueInterface;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

abstract class SwooleServerTestCase extends TestCase
{
    public function createContainer(): ContainerInterface
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder
            ->addAwareInjection(AwareInjection::create(LoggerAwareInterface::class))
            ->addDefinitions([
                ServerConfig::class => new ServerConfig('test_server', [
                    ServerSetting::DAEMONIZE => 0,
                    ServerSetting::TASK_WORKER_NUM => 1,
                    ServerSetting::WORKER_NUM => 1,
                ], [
                    new ServerPort('0.0.0.0', 9876, ServerType::HTTP()),
                ]),
                LoggerInterface::class => function () {
                    return new Logger('test', [new ErrorLogHandler()]);
                },
                ServerInterface::class => factory([ServerFactory::class, 'create']),
                ServerFactory::class => function (EventDispatcherInterface $eventDispatcher, LoggerInterface $logger) {
                    $serverFactory = new ServerFactory($logger);
                    $serverFactory->setEventDispatcher($eventDispatcher);

                    return $serverFactory;
                },
                QueueInterface::class => autowire(Queue::class),
                EventDispatcherInterface::class => function (ContainerInterface $container) {
                    $dispatcher = new EventDispatcher();
                    $dispatcher->addListener(StartEvent::class, $container->get(StartEventListener::class));
                    $dispatcher->addListener(ManagerStartEvent::class, $container->get(ManagerStartEventListener::class));

                    return $dispatcher;
                },
            ]);

        return $containerBuilder->build();
    }
}
