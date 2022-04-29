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

namespace kuiper\di;

use function DI\autowire;
use DI\Definition\FactoryDefinition;
use DI\Definition\ObjectDefinition;
use kuiper\di\fixtures\FooLoggerAware;
use kuiper\logger\LoggerFactoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class AwareAutowiringTest extends TestCase
{
    protected function tearDown(): void
    {
        \Mockery::close();
    }

    public function testAware(): void
    {
        $builder = new ContainerBuilder();
        $builder->addAwareInjection(AwareInjection::create(LoggerAwareInterface::class));
        $logger = \kuiper\logger\Logger::nullLogger();
        $builder->addDefinitions([
            LoggerInterface::class => $logger,
            'fooWithLogger' => autowire(FooLoggerAware::class)
                ->method('setLogger', \kuiper\logger\Logger::nullLogger()),
        ]);
        $container = $builder->build();
        $foo = $container->get(FooLoggerAware::class);
        $this->assertSame($logger, $foo->getLogger());

        $fooWithLogger = $container->get('fooWithLogger');
        $this->assertNotSame($logger, $fooWithLogger->getLogger());
    }

    public function testFactory(): void
    {
        $builder = new ContainerBuilder();
        $mock = \Mockery::mock(LoggerFactoryInterface::class);
        $mock->shouldReceive('create')
            ->andReturn(\Mockery::mock(LoggerInterface::class));

        $builder->addDefinitions([
            LoggerFactoryInterface::class => $mock,
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
        $logger = $foo->getLogger();
        // print_r($logger);
        $this->assertNotNull($logger);
    }
}
