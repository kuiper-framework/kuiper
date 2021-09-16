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

namespace kuiper\swoole\event;

use kuiper\swoole\constants\Event;
use kuiper\swoole\fixtures\FooMessage;
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

    public function testCreatePipeMessage()
    {
        $message = new FooMessage('key');
        /** @var PipeMessageEvent $event */
        $event = $this->factory->create(Event::PIPE_MESSAGE, [null, 1, serialize($message)]);
        // print_r($event->getMessage());
        $this->assertInstanceOf(FooMessage::class, $event->getMessage());
    }
}
