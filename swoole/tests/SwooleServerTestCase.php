<?php

declare(strict_types=1);

namespace kuiper\swoole;

use function DI\autowire;
use DI\ContainerBuilder;
use function DI\get;
use kuiper\swoole\event\ManagerStartEvent;
use kuiper\swoole\event\StartEvent;
use kuiper\swoole\listener\ManagerStartEventListener;
use kuiper\swoole\listener\StartEventListener;
use kuiper\swoole\task\Queue;
use kuiper\swoole\task\QueueInterface;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

abstract class SwooleServerTestCase extends TestCase
{
    public function createContainer(): ContainerInterface
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->useAutowiring(true)
            ->addDefinitions([
                ServerConfig::class => new ServerConfig('test_server', [
                    'daemonize' => 1,
                    'task_worker_num' => 1,
                    'worker_num' => 1,
                ], [
                    new ServerPort('0.0.0.0', 9876, ServerType::HTTP()),
                ]),
                LoggerInterface::class => function () {
                    return new Logger('test', [new ErrorLogHandler()]);
                },
                ServerInterface::class => autowire(SwooleServer::class)
                    ->method('setLogger', get(LoggerInterface::class)),
                SwooleServer::class => get(ServerInterface::class),
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
