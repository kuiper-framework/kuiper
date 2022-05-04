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

namespace kuiper\swoole\pool;

use kuiper\swoole\fixtures\FooConnection;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ConnectionProxyGeneratorTest extends TestCase
{
    public function testCode(): void
    {
        $generator = new ConnectionProxyGenerator();
        $result = $generator->generate(ContainerInterface::class);
        $this->assertEquals($result->getCode(), file_get_contents(__DIR__.'/../fixtures/ContainerProxy.txt'));
    }

    public function testCreateProxy(): void
    {
        $generator = new ConnectionProxyGenerator();
        $result = $generator->generate(ContainerInterface::class);
        // echo $result->getCode();
        $result->eval();
        $class = $result->getClassName();
        $reader = new $class(new SingleConnectionPool('reader', function () {
            return \Mockery::mock(ContainerInterface::class);
        }, new PoolConfig()));
        $this->assertInstanceOf(ContainerInterface::class, $reader);
    }

    public function testDestructNotCall(): void
    {
        $poolFactory = new PoolFactory();
        $test = static function () use ($poolFactory) {
            $object = ConnectionProxyGenerator::create($poolFactory, FooConnection::class, static function () {
                throw new \InvalidArgumentException('should not call');
            });
            unset($object);
        };
        $test();
        $this->assertTrue(true);
    }
}
