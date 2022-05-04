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

namespace kuiper\swoole\logger;

use PHPUnit\Framework\TestCase;

class DateFormatterTest extends TestCase
{
    protected function setUp(): void
    {
        date_default_timezone_set('Asia/Shanghai');
    }

    public function testName(): void
    {
        $formatter = new DateFormatter('Y-m-d H:i:s.v');
        $this->assertEquals('2021-09-26 10:18:36.804', $formatter->format(1632622716.804));
    }

    public function testFormat()
    {
        $formatter = new DateFormatter('d/M/Y:H:i:s O');
        $this->assertEquals('26/Sep/2021:10:18:36 +0800', $formatter->format(1632622716.804));
    }
}
