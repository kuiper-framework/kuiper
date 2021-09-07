<?php

declare(strict_types=1);

namespace kuiper\rpc\servicediscovery\loadbalance;

use PHPUnit\Framework\TestCase;

class EqualityTest extends TestCase
{
    public function testName()
    {
        $lb = new Equality(['a', 'b', 'c']);
        $this->assertEquals('a', $lb->select());
        $this->assertEquals('b', $lb->select());
        $this->assertEquals('c', $lb->select());
        $this->assertEquals('a', $lb->select());
    }
}
