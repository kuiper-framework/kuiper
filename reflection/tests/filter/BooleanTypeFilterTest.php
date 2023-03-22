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

namespace kuiper\reflection\filter;

class BooleanTypeFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider valuesForValidate
     */
    public function testValidate($value, $valid)
    {
        $filter = new BooleanTypeFilter();
        $this->assertEquals($valid, $filter->isValid($value));
    }

    public static function valuesForValidate()
    {
        foreach (['1', 'true', 'on', 'yes', true, '0', 'false', 'off', 'no', '', false, null] as $value) {
            yield [$value, true];
        }
        foreach (['xxx', 3] as $value) {
            yield [$value, false];
        }
    }

    /**
     * @dataProvider valuesForSanitize
     *
     * @param $value
     * @param $expected
     */
    public function testSanitize($value, $expected)
    {
        $filter = new BooleanTypeFilter();
        $this->assertEquals($expected, $filter->sanitize($value));
    }

    public static function valuesForSanitize()
    {
        foreach (['1', 'true', 'on', 'yes', true] as $value) {
            yield [$value, true];
        }
        foreach (['0', 'false', 'off', 'no', '', false, null] as $value) {
            yield [$value, false];
        }
        foreach (['xxx', 3] as $value) {
            yield [$value, false];
        }
    }
}
