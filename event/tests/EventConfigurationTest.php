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

use function DI\factory;

use kuiper\di\ContainerBuilder;
use kuiper\di\PropertiesDefinitionSource;
use kuiper\event\fixtures\FooEvent;
use kuiper\event\fixtures\FooEventListener;
use kuiper\helper\Properties;
use kuiper\helper\PropertyResolverInterface;
use kuiper\logger\LoggerFactory;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\pool\PoolFactory;
use kuiper\swoole\pool\PoolFactoryInterface;
use kuiper\swoole\server\ServerInterface;
use kuiper\swoole\task\QueueInterface;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class EventConfigurationTest extends TestCase
{
    public function testAddListener(): void
    {
        $container = $this->createContainer([
            'application' => [
                'listeners' => [
                    FooEventListener::class,
                ],
                'swoole' => [
                    'task_worker_num' => 1,
                ],
            ],
        ]);
        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        $this->assertInstanceOf(AsyncEventDispatcher::class, $eventDispatcher);
        /** @var AsyncEventDispatcher $eventDispatcher */
        $listeners = $eventDispatcher->getDelegateEventDispatcher()
            ->getDelegateEventDispatcher()->getListeners(FooEvent::class);
        $this->assertCount(1, $listeners);
        $eventDispatcher->dispatch(new FooEvent());
    }

    protected function createContainer(array $config): ContainerInterface
    {
        $builder = new ContainerBuilder();
        $config = Properties::create($config);
        $builder->addConfiguration(new EventConfiguration());
        $builder->addDefinitions(new PropertiesDefinitionSource($config));
        $server = Mockery::mock(ServerInterface::class);
        $server->shouldReceive('isTaskWorker')
            ->andReturn(false);
        $queue = Mockery::mock(QueueInterface::class);
        $queue->shouldReceive('put');
        $builder->addDefinitions([
            PropertyResolverInterface::class => $config,
            PoolFactoryInterface::class => new PoolFactory(),
            LoggerInterface::class => factory(function (LoggerFactoryInterface $loggerFactory) {
                return $loggerFactory->create();
            }),
            LoggerFactoryInterface::class => factory(function (ContainerInterface $container) {
                return new LoggerFactory($container, [
                    'loggers' => [
                        'root' => ['console' => true],
                    ],
                ]);
            }),
            ServerInterface::class => $server,
            QueueInterface::class => $queue,
        ]);

        return $builder->build();
    }
}
