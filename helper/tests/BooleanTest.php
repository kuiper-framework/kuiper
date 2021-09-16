<?php

declare(strict_types=1);

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace kuiper\helper;

class BooleanTest extends TestCase
{
    /**
     * @dataProvider booleans
     */
    public function testValueOf($value, $expect)
    {
        $val = Boolean::valueOf($value);
        // var_export([$value, $val, $expect]);
        $this->assertEquals($val, $expect);
    }

    public function booleans()
    {
        return [
            [1, true],
            ['1', true],
            ['true', true],
            [true, true],
            [0, false],
            ['0', false],
            ['false', false],
            [false, false],
            ['t', null],
            [2, null],
        ];
    }
}
