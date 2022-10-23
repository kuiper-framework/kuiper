<?php

declare(strict_types=1);

namespace kuiper\swoole\constants;

use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    public function testName(): void
    {
        $event = Event::BOOTSTRAP;
        $this->assertFalse($event->isSwooleEvent());
    }
}
