<?php

declare(strict_types=1);

namespace kuiper\swoole\event;

use kuiper\swoole\constants\Event;
use PHPUnit\Framework\TestCase;

class SwooleServerEventFactoryTest extends TestCase
{
    /**
     * @var ServerEventFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new ServerEventFactory();
    }

    public function testCreate()
    {
        $event = $this->factory->create(Event::START, []);
        $this->assertInstanceOf(StartEvent::class, $event);
    }
}
