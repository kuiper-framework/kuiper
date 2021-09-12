<?php

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
            ],
        ])->get(EventDispatcherInterface::class);
        $listeners = $eventDispatcher->getListeners(FooEvent::class);
        $this->assertCount(1, $listeners);
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
        ]);

        return $builder->build();
    }
}
