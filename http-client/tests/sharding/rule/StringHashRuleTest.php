<?php

declare(strict_types=1);

namespace kuiper\http\client\sharding\rule;

use kuiper\db\sharding\rule\StringHashRule;
use PHPUnit\Framework\TestCase;

class StringHashRuleTest extends TestCase
{
    public function testStringHash()
    {
        $rule = new StringHashRule('door_code', 16);
        $part = $rule->getPartition(['door_code' => 'a']);
        $this->assertEquals(3, $part);
    }
}
