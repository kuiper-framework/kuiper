<?php

declare(strict_types=1);

namespace kuiper\swoole\event;

use kuiper\swoole\SwooleEvent;
use kuiper\swoole\SwooleServer;
use PHPUnit\Framework\TestCase;

class SwooleServerEventFactoryTest extends TestCase
{
    /**
     * @var \Mockery\MockInterface|SwooleServer
     */
    private $server;
    /**
     * @var SwooleServerEventFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->server = \Mockery::mock(SwooleServer::class);
        $this->factory = new SwooleServerEventFactory($this->server);
    }

    public function testCreate()
    {
        $event = $this->factory->create(SwooleEvent::START, []);
        $this->assertInstanceOf(StartEvent::class, $event);
        $this->assertEquals($this->server, $event->getServer());
    }
}
