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
