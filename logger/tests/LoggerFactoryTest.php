<?php

declare(strict_types=1);

namespace kuiper\logger;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class LoggerFactoryTest extends TestCase
{
    /**
     * @var LoggerFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new LoggerFactory(new NullLogger(), [
            LoggerFactory::ROOT => LogLevel::DEBUG,
            'com' => [
                LoggerFactory::ROOT => LogLevel::INFO,
                'github' => [
                    LoggerFactory::ROOT => LogLevel::ERROR,
                ],
            ],
        ]);
    }

    public function dataProvider()
    {
        return [
            // ['Foo', LogLevel::DEBUG],
            ['com\\Foo', LogLevel::INFO],
            ['kuiper\\Foo', LogLevel::DEBUG],
            ['com\\github\\Foo', LogLevel::ERROR],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testCreate($class, $level)
    {
        $logger = $this->factory->create($class);
        $this->assertLogLevel($level, $logger);
    }

    private function assertLogLevel(string $level, \Psr\Log\LoggerInterface $logger)
    {
        $property = new \ReflectionProperty($logger, 'logLevel');
        $property->setAccessible(true);
        $this->assertEquals(Logger::getLevel($level), $property->getValue($logger));
    }
}
