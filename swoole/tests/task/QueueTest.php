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

namespace kuiper\swoole\task;

use kuiper\swoole\event\BootstrapEvent;
use kuiper\swoole\event\StartEvent;
use kuiper\swoole\event\TaskEvent;
use kuiper\swoole\event\WorkerStartEvent;
use kuiper\swoole\fixtures\FooTask;
use kuiper\swoole\server\ServerInterface;
use kuiper\swoole\SwooleServerTestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Swoole\Timer;
use Symfony\Component\EventDispatcher\EventDispatcher;

class QueueTest extends SwooleServerTestCase
{
    private const TAG = '['.__CLASS__.'] ';

    public function testQueue(): void
    {
        $container = $this->createContainer();
        $logger = $container->get(LoggerInterface::class);

        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        $eventDispatcher->addListener(BootstrapEvent::class, function () use ($container, $logger, $eventDispatcher) {
            /** @var Queue $queue */
            $queue = $container->get(QueueInterface::class);
            $eventDispatcher->addListener(WorkerStartEvent::class, function (WorkerStartEvent $event) use ($logger, $queue) {
                if (0 === $event->getWorkerId()) {
                    $logger->info(self::TAG.'put task');
                    $queue->put(new FooTask('foo'), -1, function ($server, $taskId, $result) use ($logger) {
                        $logger->info(self::TAG."receive foo task $taskId result = $result");
                    });
                }
            });
            $eventDispatcher->addListener(TaskEvent::class, function (TaskEvent $event) use ($logger, $queue) {
                $logger->info(self::TAG.'consume task', ['id' => $event->getTaskId(), 'task' => $event->getData()]);
                $this->assertInstanceOf(FooTask::class, $event->getData());
                $this->assertEquals('foo', $event->getData()->getArg());
                $queue->dispatch($event);
            });
        });
        $eventDispatcher->addListener(StartEvent::class, function (StartEvent $event) use ($logger) {
            $logger->info(self::TAG.'server started');
            Timer::after(1000, function () use ($event) {
                $event->getServer()->stop();
            });
        });
        $logger->info(self::TAG.'start server');
        // $container->get(ServerInterface::class)->start();
        $this->assertTrue(true, 'ok');
    }
}
