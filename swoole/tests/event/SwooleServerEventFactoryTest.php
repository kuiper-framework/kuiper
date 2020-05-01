<?php

declare(strict_types=1);

namespace kuiper\swoole\event;

use kuiper\swoole\constants\Event;
use kuiper\swoole\SwooleServer;
use PHPUnit\Framework\TestCase;

class SwooleServerEventFactoryTest extends TestCase
{
    /**
     * @var \Mockery\MockInterface|SwooleServer
     */
    private $server;
    /**
     * @var ServerEventFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->server = \Mockery::mock(SwooleServer::class);
        $this->factory = new ServerEventFactory($this->server);
    }

    public function testCreate()
    {
        $event = $this->factory->create(Event::START, []);
        $this->assertInstanceOf(StartEvent::class, $event);
        $this->assertEquals($this->server, $event->getServer());
    }
}
