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

namespace kuiper\rpc\servicediscovery\loadbalance;

use PHPUnit\Framework\TestCase;

class EqualityTest extends TestCase
{
    public function testName(): void
    {
        $lb = new Equality(['a', 'b', 'c']);
        $this->assertEquals('a', $lb->select());
        $this->assertEquals('b', $lb->select());
        $this->assertEquals('c', $lb->select());
        $this->assertEquals('a', $lb->select());
    }
}
