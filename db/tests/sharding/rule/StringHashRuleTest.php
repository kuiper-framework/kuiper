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

namespace kuiper\db\sharding\rule;

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
