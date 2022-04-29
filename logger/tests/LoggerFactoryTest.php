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

namespace kuiper\logger;

use kuiper\swoole\logger\CoroutineIdProcessor;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LoggerFactoryTest extends TestCase
{
    private LoggerFactory $factory;

    protected function setUp(): void
    {
        $container = \Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->andReturnUsing(function ($name) {
                return new $name();
            });
        $this->factory = new LoggerFactory($container, [
            'loggers' => [
                'root' => [
                    'console' => true,
                    'level' => LogLevel::DEBUG,
                ],
                'AccessLogger' => [
                    'handlers' => [
                        [
                            'handler' => [
                                'class' => TestHandler::class,
                            ],
                        ],
                    ],
                    'processors' => [
                        CoroutineIdProcessor::class,
                    ],
                ],
            ],
            'level' => [
                'com' => LogLevel::INFO,
                'com\\github' => LogLevel::ERROR,
            ],
            'logger' => [
                'foo\\AccessLog' => 'AccessLogger',
                'bar' => 'AccessLogger',
            ],
        ]);
    }

    public function dataProvider(): array
    {
        return [
            // ['Foo', LogLevel::DEBUG],
            ['com\\Foo', Logger::class, LogLevel::INFO],
            ['kuiper\\Foo', \Monolog\Logger::class, null],
            ['com\\github\\Foo', Logger::class, LogLevel::ERROR],
            ['foo\\AccessLog', \Monolog\Logger::class, null],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testCreate($class, $loggerClass, $level): void
    {
        $logger = $this->factory->create($class);
        $this->assertInstanceOf($loggerClass, $logger);
        if ($logger instanceof Logger) {
            $this->assertLogLevel($level, $logger);
        }
    }

    private function assertLogLevel(string $level, LoggerInterface $logger): void
    {
        $property = new \ReflectionProperty($logger, 'logLevel');
        $property->setAccessible(true);
        $this->assertEquals(Logger::getLevel($level), $property->getValue($logger));
    }

    public function testGetFoo(): void
    {
        $logger = $this->factory->create('foo\\AccessLog');
        $logger->info('test');

        $logger = $this->factory->create('bar\\AccessLog');
        $logger->info('test');
        $this->assertTrue(true);
    }
}
