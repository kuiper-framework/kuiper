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
