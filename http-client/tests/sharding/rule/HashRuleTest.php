<?php

declare(strict_types=1);

namespace kuiper\http\client\sharding\rule;

use kuiper\db\sharding\rule\HashRule;
use PHPUnit\Framework\TestCase;

class HashRuleTest extends TestCase
{
    public function testHash()
    {
        $rule = new HashRule('client_id', 16);
        $this->assertEquals(4, $rule->getPartition(['client_id' => 20]));
    }
}
