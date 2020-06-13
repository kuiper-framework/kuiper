<?php

declare(strict_types=1);

namespace kuiper\di;

use function DI\autowire;
use DI\Definition\FactoryDefinition;
use DI\Definition\ObjectDefinition;
use kuiper\di\fixtures\FooLoggerAware;
use kuiper\logger\Logger;
use kuiper\logger\LoggerFactory;
use kuiper\logger\LoggerFactoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class AwareAutowiringTest extends TestCase
{
    public function testAware()
    {
        $builder = new ContainerBuilder();
        $builder->addAwareInjection(AwareInjection::create(LoggerAwareInterface::class));
        $logger = new NullLogger();
        $builder->addDefinitions([
            LoggerInterface::class => $logger,
            'fooWithLogger' => autowire(FooLoggerAware::class)
                ->method('setLogger', new NullLogger()),
        ]);
        $container = $builder->build();
        $foo = $container->get(FooLoggerAware::class);
        $this->assertSame($logger, $foo->getLogger());

        $fooWithLogger = $container->get('fooWithLogger');
        $this->assertNotSame($logger, $fooWithLogger->getLogger());
    }

    public function testFactory()
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions([
            LoggerFactoryInterface::class => new LoggerFactory(new NullLogger()),
        ]);
        $builder->addAwareInjection(new AwareInjection(
            LoggerAwareInterface::class,
            'setLogger',
            static function (ObjectDefinition $definition) {
                return [
                new FactoryDefinition(
                    $definition->getName().'.logger',
                    static function (LoggerFactoryInterface $loggerFactory) use ($definition) {
                        return $loggerFactory->create($definition->getClassName());
                    }
                ),
            ];
            }));
        $container = $builder->build();
        $foo = $container->get(FooLoggerAware::class);
        // print_r($foo);
        $this->assertInstanceOf(Logger::class, $foo->getLogger());
    }
}
