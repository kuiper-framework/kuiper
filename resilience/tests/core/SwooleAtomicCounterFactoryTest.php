<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

use PHPUnit\Framework\TestCase;

class SwooleAtomicCounterFactoryTest extends TestCase
{
    public function testName()
    {
        $counterFactory = new SwooleAtomicCounterFactory();
        $counter = $counterFactory->create('a');

        $counter->set(10);
        $this->assertEquals(11, $counter->increment());
        $this->assertEquals(11, $counter->get());
        $this->assertEquals(10, $counter->decrement());
        $this->assertEquals(10, $counter->get());
    }
}
