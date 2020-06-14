<?php

declare(strict_types=1);

namespace kuiper\logger;

use kuiper\swoole\monolog\CoroutineIdProcessor;
use Monolog\Handler\RotatingFileHandler;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LogLevel;

class LoggerFactoryTest extends TestCase
{
    /**
     * @var LoggerFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $container = \Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->andReturn(function ($name) {
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
                                'class' => RotatingFileHandler::class,
                                'constructor' => [
                                    'filename' => 'access.log',
                                ],
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
            'config' => [
                'foo\\AccessLog' => 'AccessLogger',
            ],
        ]);
    }

    public function dataProvider()
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
    public function testCreate($class, $loggerClass, $level)
    {
        $logger = $this->factory->create($class);
        $this->assertInstanceOf($loggerClass, $logger);
        if ($logger instanceof Logger) {
            $this->assertLogLevel($level, $logger);
        }
    }

    private function assertLogLevel(string $level, \Psr\Log\LoggerInterface $logger)
    {
        $property = new \ReflectionProperty($logger, 'logLevel');
        $property->setAccessible(true);
        $this->assertEquals(Logger::getLevel($level), $property->getValue($logger));
    }
}
