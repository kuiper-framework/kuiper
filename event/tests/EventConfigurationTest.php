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

use function DI\autowire;
use function DI\factory;
use kuiper\annotations\AnnotationReader;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\ContainerBuilder;
use kuiper\di\PropertiesDefinitionSource;
use kuiper\event\fixtures\FooEvent;
use kuiper\event\fixtures\FooEventListener;
use kuiper\helper\Properties;
use kuiper\helper\PropertyResolverInterface;
use kuiper\logger\LoggerFactory;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\pool\PoolFactory;
use kuiper\swoole\pool\PoolFactoryInterface;
use kuiper\swoole\server\ServerInterface;
use kuiper\swoole\ServerConfig;
use kuiper\swoole\ServerFactory;
use kuiper\swoole\ServerPort;
use kuiper\swoole\task\Queue;
use kuiper\swoole\task\QueueInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class EventConfigurationTest extends TestCase
{
    public function testAddListener()
    {
        $eventDispatcher = $this->createContainer([
            'application' => [
                'listeners' => [
                    FooEventListener::class,
                ],
                'swoole' => [
                    'task_worker_num' => 1,
                ],
            ],
        ])->get(EventDispatcherInterface::class);
        $this->assertInstanceOf(AsyncEventDispatcher::class, $eventDispatcher);
        /** @var AsyncEventDispatcher $eventDispatcher */
        $listeners = $eventDispatcher->getDelegateEventDispatcher()->getListeners(FooEvent::class);
        $this->assertCount(1, $listeners);
        $eventDispatcher->dispatch(new FooEvent());
    }

    /**
     * @return ContainerInterface
     */
    protected function createContainer(array $config): ContainerInterface
    {
        $builder = new ContainerBuilder();
        $config = Properties::create($config);
        $builder->addConfiguration(new EventConfiguration());
        $builder->addDefinitions(new PropertiesDefinitionSource($config));
        $builder->addDefinitions([
            PropertyResolverInterface::class => $config,
            AnnotationReaderInterface::class => AnnotationReader::getInstance(),
            PoolFactoryInterface::class => new PoolFactory(),
            LoggerInterface::class => factory(function (LoggerFactoryInterface $loggerFactory) {
                return $loggerFactory->create();
            }),
            ServerInterface::class => factory(function (ContainerInterface $container) {
                $port = new ServerPort('0.0.0.0', 8888, ServerType::TCP);
                $serverConfig = new ServerConfig('app', [$port]);
                $serverFactory = new ServerFactory();
                $serverFactory->setEventDispatcher($container->get(EventDispatcherInterface::class));

                return $serverFactory->create($serverConfig);
            }),
            LoggerFactoryInterface::class => factory(function (ContainerInterface $container) {
                return new LoggerFactory($container, [
                    'loggers' => [
                        'root' => ['console' => true],
                    ],
                ]);
            }),
            QueueInterface::class => autowire(Queue::class),
        ]);

        return $builder->build();
    }
}
